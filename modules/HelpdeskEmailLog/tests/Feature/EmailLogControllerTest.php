<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogClick;
use Modules\HelpdeskEmailLog\Models\EmailLogLink;
use Modules\HelpdeskEmailLog\Models\EmailLogOpen;
use Tests\TestCase;

class EmailLogControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — sin declararla, cada
    // EmailLog::factory()->create() de este archivo escribe una fila REAL
    // sin rollback (mismo gotcha ya documentado en varios tests del módulo,
    // p. ej. BounceMailboxesControllerTest). Confirmado en vivo: sin este
    // fix, 45 filas fixture de este mismo archivo (subjects 'Other message'/
    // 'Related one'/'Unrelated') habían quedado filtradas en la BD real de
    // ejecuciones pasadas, causando 2 falsos negativos deterministas
    // (index/show con datos ajenos filtrando por búsqueda/destinatario).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailLogPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemaillog.view');
    }

    private function manager(): User
    {
        return tap(User::factory()->create())->givePermissionTo(['helpdeskemaillog.view', 'helpdeskemaillog.manage']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('helpdeskemaillog.index'))->assertRedirect();
    }

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskemaillog.index'))
            ->assertForbidden();
    }

    public function test_index_renders_with_logs_and_stats(): void
    {
        // Delta sobre lo preexistente: la BD de test es compartida y puede
        // arrastrar filas residuales de otros runs.
        $baseTotal = EmailLog::query()->count();
        $baseFailed = EmailLog::query()->where('status', 'failed')->count();

        EmailLog::factory()->count(3)->create();
        EmailLog::factory()->failed()->create();

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.index')
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === $baseTotal + 4 && $stats['failed'] === $baseFailed + 1);
    }

    public function test_index_filters_by_status_and_module(): void
    {
        EmailLog::factory()->forModule('Auth')->create(['subject' => 'Reset link']);
        EmailLog::factory()->forModule('Newsletter')->failed()->create(['subject' => 'Weekly digest']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Weekly digest')
            ->assertDontSee('Reset link');

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['module' => 'Auth']))
            ->assertOk()
            ->assertSee('Reset link')
            ->assertDontSee('Weekly digest');
    }

    /**
     * show() ya no pinta una página propia ("preview") — pinta el mismo
     * workspace combinado que index() (lista + detalle + sidebar), con este
     * email como seleccionado (ver EmailLogController::renderWorkspace()).
     */
    public function test_show_displays_the_workspace_with_the_email_selected(): void
    {
        $log = EmailLog::factory()->create(['subject' => 'Order confirmation']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.index')
            ->assertViewHas('log', fn ($selected) => $selected->is($log))
            ->assertSee('Order confirmation');
    }

    public function test_show_returns_404_for_unknown_uid(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', Str::orderedUuid()))
            ->assertNotFound();
    }

    /**
     * Clic AJAX sobre una fila del listado: show() debe devolver SOLO el
     * fragmento de detalle+sidebar (emails/partials/detail-panel.blade.php),
     * nunca la página completa con el menú lateral de webadmin — el frontend
     * lo inserta dentro de la columna central ya presente en la pantalla, sin
     * recargar. Detección AJAX = $request->ajax() || $request->wantsJson(),
     * mismo criterio que ConversationsController::update() (módulo Helpdesk,
     * el único otro punto del código que decide entre fragmento parcial y
     * navegación completa) — axios (el cliente HTTP del frontend en todo el
     * proyecto) ya manda X-Requested-With por defecto, ver
     * resources/js/bootstrap.js.
     */
    public function test_show_returns_only_the_detail_fragment_for_ajax_requests(): void
    {
        $log = EmailLog::factory()->create(['subject' => 'Fragment only please']);

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertViewIs('helpdeskemaillog::emails.partials.detail-panel');

        $content = $response->getContent();
        $this->assertStringContainsString('Fragment only please', $content);

        // Marcador exclusivo de layouts.theme (modules/Theme/resources/views/
        // layouts/theme.blade.php) — si apareciera aquí, se estaría
        // devolviendo la página completa en vez del fragmento.
        $this->assertStringNotContainsString('id="mc-app"', $content);
    }

    /**
     * Sin cabecera AJAX, la misma ruta show() sigue funcionando como acceso
     * directo/deep-link: página completa combinada, con el email seleccionado.
     */
    public function test_show_returns_the_full_page_without_ajax_header(): void
    {
        $log = EmailLog::factory()->create(['subject' => 'Full page please']);

        $response = $this->actingAs($this->viewer())->get(route('helpdeskemaillog.show', $log->uid));

        $response->assertOk()->assertViewIs('helpdeskemaillog::emails.index');
        $this->assertStringContainsString('id="mc-app"', $response->getContent());
    }

    /**
     * Ejercita show()->clicksSummary()/opensSummary() de punta a punta
     * (query + render del Blade), no solo su presencia en el array de la
     * vista — un fallo aquí (p. ej. una clase que no resuelve) sí se
     * refleja como 500, a diferencia de solo comprobar assertViewHas().
     */
    public function test_show_displays_opens_and_clicks_summary_when_tracked(): void
    {
        $log = EmailLog::factory()->tracked()->create(['subject' => 'Tracked order confirmation']);

        EmailLogOpen::create([
            'email_log_id' => $log->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 test',
            'opened_at' => now()->subMinutes(10),
        ]);

        $link = EmailLogLink::create([
            'email_log_id' => $log->id,
            'token' => Str::random(40),
            'url' => 'https://example.com/ticket/123',
            'created_at' => now()->subMinutes(9),
        ]);

        EmailLogClick::create([
            'email_log_link_id' => $link->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 test',
            'clicked_at' => now()->subMinutes(8),
        ]);

        // El HTML del detalle (URL del clic incluida) todavía no lo pinta
        // emails/index.blade.php — pendiente del agente de frontend (ver
        // emails/partials/detail-panel.blade.php) — así que aquí se verifica
        // el dato ya resuelto en la vista, no su renderizado en el DOM.
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.index')
            ->assertViewHas('opensSummary', fn ($summary) => $summary['count'] === 1)
            ->assertViewHas('clicksSummary', fn ($summary) => $summary['count'] === 1
                && $summary['unique_links'] === 1
                && $summary['recent']->first()->link_url === 'https://example.com/ticket/123');
    }

    /**
     * Un envío con tracking activado pero sin ningún hit real (nadie abrió
     * ni hizo clic todavía) — hasOpenTracking()/hasClickTracking() igual
     * disparan la query de resumen, así que este camino se ejercita aunque
     * el conteo real sea cero.
     */
    public function test_show_displays_zero_state_when_tracked_but_no_hits_yet(): void
    {
        $log = EmailLog::factory()->tracked()->create(['subject' => 'Tracked, not opened yet']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewHas('opensSummary', fn ($summary) => $summary['count'] === 0)
            ->assertViewHas('clicksSummary', fn ($summary) => $summary['count'] === 0);
    }

    /**
     * EngagementBotHeuristics se ejercita en profundidad en su propio test
     * unitario — aquí solo se confirma que EmailLogController de verdad la
     * invoca y expone likely_bot_count/likely_bot en la vista (el tipo de
     * cableado que un test unitario aislado nunca detecta).
     */
    public function test_show_flags_likely_bot_opens_and_clicks(): void
    {
        $log = EmailLog::factory()->tracked()->create(['sent_at' => now()->subMinutes(10)]);

        EmailLogOpen::create([
            'email_log_id' => $log->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 GoogleImageProxy',
            'opened_at' => now()->subMinutes(9),
        ]);

        $link = EmailLogLink::create([
            'email_log_id' => $log->id,
            'token' => Str::random(40),
            'url' => 'https://example.com/x',
            'created_at' => now()->subMinutes(9),
        ]);

        EmailLogClick::create([
            'email_log_link_id' => $link->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'Mimecast-SEG',
            'clicked_at' => now()->subMinutes(8),
        ]);

        // El badge "likely_bot" todavía no lo pinta emails/index.blade.php
        // (pendiente del agente de frontend) — se verifica el dato, no el
        // texto renderizado (mismo motivo que el test de arriba).
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewHas('opensSummary', fn ($summary) => $summary['likely_bot_count'] === 1 && $summary['recent']->first()->likely_bot === true)
            ->assertViewHas('clicksSummary', fn ($summary) => $summary['likely_bot_count'] === 1 && $summary['recent']->first()->likely_bot === true);
    }

    /**
     * El listado también consulta opens/clicks (withCount en index()) —
     * mismo tipo de gap que show(): un fallo en la relación/consulta se
     * refleja como 500 al renderizar, no solo como un dato ausente.
     */
    public function test_index_shows_engagement_counts_for_tracked_logs(): void
    {
        $log = EmailLog::factory()->tracked()->create(['subject' => 'Tracked in list']);

        EmailLogOpen::create([
            'email_log_id' => $log->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 test',
            'opened_at' => now(),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'Tracked in list']))
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->first()?->opens_count === 1 && $logs->first()?->clicks_count === 0);
    }

    public function test_index_filters_by_engagement(): void
    {
        $opened = EmailLog::factory()->tracked()->create(['subject' => 'Opened one']);
        EmailLogOpen::create([
            'email_log_id' => $opened->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'test',
            'opened_at' => now(),
        ]);

        $notOpened = EmailLog::factory()->tracked()->create(['subject' => 'Not opened one']);

        $link = EmailLogLink::create([
            'email_log_id' => $opened->id,
            'token' => Str::random(40),
            'url' => 'https://example.com/x',
            'created_at' => now(),
        ]);
        EmailLogClick::create([
            'email_log_link_id' => $link->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'test',
            'clicked_at' => now(),
        ]);

        // Sin seguimiento en absoluto: no debe aparecer ni en "abiertos" ni
        // en "sin abrir" (que exige metadata->open_tracking_enabled).
        $untracked = EmailLog::factory()->create(['subject' => 'Untracked one']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['engagement' => 'opened', 'search' => 'one']))
            ->assertSee('Opened one')
            ->assertDontSee('Not opened one')
            ->assertDontSee('Untracked one');

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['engagement' => 'not_opened', 'search' => 'one']))
            ->assertSee('Not opened one')
            ->assertDontSee('Opened one')
            ->assertDontSee('Untracked one');

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['engagement' => 'clicked', 'search' => 'one']))
            ->assertSee('Opened one')
            ->assertDontSee('Not opened one');

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['engagement' => 'not_clicked', 'search' => 'one']))
            ->assertSee('Not opened one')
            ->assertDontSee('Opened one');
    }

    public function test_index_computes_open_and_click_rates(): void
    {
        $baseOpenTracked = EmailLog::query()->where('metadata->open_tracking_enabled', true)->count();
        $baseOpened = EmailLog::query()->where('metadata->open_tracking_enabled', true)->whereHas('opens')->count();

        $opened = EmailLog::factory()->tracked()->create();
        EmailLogOpen::create([
            'email_log_id' => $opened->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'test',
            'opened_at' => now(),
        ]);
        EmailLog::factory()->tracked()->create(); // tracked pero sin apertura

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewHas('stats', function ($stats) use ($baseOpenTracked, $baseOpened) {
                return $stats['open_tracked'] === $baseOpenTracked + 2
                    && $stats['opened'] === $baseOpened + 1;
            });
    }

    public function test_destroy_requires_manage_permission(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs($this->viewer())
            ->delete(route('helpdeskemaillog.destroy', $log->uid))
            ->assertForbidden();

        $this->assertDatabaseHas('email_logs', ['id' => $log->id]);
    }

    public function test_manager_can_delete_a_log(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.destroy', $log->uid))
            ->assertRedirect(route('helpdeskemaillog.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('email_logs', ['id' => $log->id]);
    }

    public function test_manager_can_bulk_delete_logs(): void
    {
        $logs = EmailLog::factory()->count(3)->create();
        $keep = EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => $logs->pluck('uid')->all()])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Robusto a filas residuales en la BD compartida: se asserta el efecto
        // sobre las filas creadas por ESTE test, no el total global.
        foreach ($logs as $deleted) {
            $this->assertDatabaseMissing('email_logs', ['id' => $deleted->id]);
        }
        $this->assertDatabaseHas('email_logs', ['id' => $keep->id]);
    }

    public function test_bulk_delete_validates_input(): void
    {
        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => []])
            ->assertSessionHasErrors('uids');
    }

    public function test_export_returns_a_csv_download(): void
    {
        EmailLog::factory()->create(['subject' => 'Exported subject']);

        $response = $this->actingAs($this->viewer())->get(route('helpdeskemaillog.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Exported subject', $response->streamedContent());
    }

    public function test_export_includes_opens_and_clicks_columns(): void
    {
        $tracked = EmailLog::factory()->tracked()->create(['subject' => 'Exported tracked']);
        EmailLogOpen::create([
            'email_log_id' => $tracked->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'test',
            'opened_at' => now(),
        ]);

        EmailLog::factory()->create(['subject' => 'Exported untracked']);

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.export', ['search' => 'Exported']));

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $header = array_shift($rows);

        $opensCol = array_search(__('helpdeskemaillog::emaillog.csv.opens'), $header, true);
        $clicksCol = array_search(__('helpdeskemaillog::emaillog.csv.clicks'), $header, true);
        $subjectCol = array_search(__('helpdeskemaillog::emaillog.csv.subject'), $header, true);

        $this->assertNotFalse($opensCol);
        $this->assertNotFalse($clicksCol);

        $trackedRow = collect($rows)->first(fn ($row) => $row[$subjectCol] === 'Exported tracked');
        $untrackedRow = collect($rows)->first(fn ($row) => $row[$subjectCol] === 'Exported untracked');

        // Con seguimiento: cuenta real (1 apertura, 0 clics, como string
        // "1"/"0"). Sin seguimiento: celda vacía, no "0" — mismo criterio de
        // honestidad que la columna "Interacción" del listado.
        $this->assertSame('1', $trackedRow[$opensCol]);
        $this->assertSame('0', $trackedRow[$clicksCol]);
        $this->assertSame('', $untrackedRow[$opensCol]);
        $this->assertSame('', $untrackedRow[$clicksCol]);
    }

    public function test_resend_dispatches_job_for_manager(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create([
            'to_addresses' => ['client@example.test'],
            'subject' => 'Please resend me',
            'status' => EmailStatus::Sent,
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(ResendEmailLogJob::class, fn (ResendEmailLogJob $job) => $job->emailLogId === $log->id);
    }

    public function test_resend_requires_manage_permission(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => ['client@example.test']]);

        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_index_ignores_invalid_date_filters(): void
    {
        EmailLog::factory()->create(['subject' => 'Visible with bad dates']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['date_from' => 'not-a-date', 'date_to' => '9999-99-99']))
            ->assertOk()
            ->assertSee('Visible with bad dates');
    }

    public function test_index_ignores_array_date_filters(): void
    {
        // ?date_from[]=x llega como array: no debe provocar un 500.
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index').'?date_from[]=2026-01-01&date_to[]=x')
            ->assertOk();
    }

    public function test_index_applies_valid_date_filters(): void
    {
        EmailLog::factory()->create([
            'subject' => 'Old email entry',
            'created_at' => now()->subYears(30),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['date_from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertDontSee('Old email entry');
    }

    public function test_resend_is_blocked_when_body_is_redacted(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create([
            'to_addresses' => ['client@example.test'],
            'body_html' => null,
            'body_text' => null,
            'metadata' => ['redacted' => true],
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_resend_is_blocked_when_body_is_truncated(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create([
            'to_addresses' => ['client@example.test'],
            'body_html' => '<p>parcial</p>'.EmailLog::TRUNCATION_MARKER,
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_bulk_resend_skips_redacted_and_truncated_logs(): void
    {
        Queue::fake();

        $ok = EmailLog::factory()->create(['to_addresses' => ['a@example.test']]);
        $redacted = EmailLog::factory()->create([
            'to_addresses' => ['b@example.test'],
            'metadata' => ['redacted' => true],
        ]);
        $truncated = EmailLog::factory()->create([
            'to_addresses' => ['c@example.test'],
            'metadata' => ['truncated' => true],
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.bulk-resend'), [
                'uids' => [$ok->uid, $redacted->uid, $truncated->uid],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(ResendEmailLogJob::class, 1);
        Queue::assertPushed(ResendEmailLogJob::class, fn (ResendEmailLogJob $job) => $job->emailLogId === $ok->id);
    }

    public function test_resend_job_refuses_to_send_redacted_body(): void
    {
        $log = EmailLog::factory()->create([
            'to_addresses' => ['client@example.test'],
            'metadata' => ['redacted' => true],
        ]);

        $before = EmailLog::query()->count();

        (new ResendEmailLogJob($log->id))->handle();

        // No se envía nada: no aparece ninguna fila nueva de log del reenvío.
        $this->assertSame($before, EmailLog::query()->count());
    }

    public function test_resend_returns_error_when_no_recipients(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => []]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    /**
     * El reenvío se envía con `Mail::html` crudo (sin Mailable), así que su fila
     * de EmailLog —creada por LogEmailQueued— no llevaba atribución. Ahora el job
     * añade cabeceras X-* que enlazan la nueva fila con el log original, para que
     * el reenvío sea trazable (módulo + entity + external_id 'resend:<id>').
     */
    public function test_resend_creates_a_log_row_traceable_to_the_original(): void
    {
        $original = EmailLog::factory()->create([
            'subject' => 'Recibo #42',
            'to_addresses' => ['cliente@example.com'],
            'body_html' => '<p>Su recibo</p>',
            'from_address' => 'ventas@example.com',
        ]);

        (new ResendEmailLogJob($original->id))->handle();

        $resend = EmailLog::query()
            ->where('id', '!=', $original->id)
            ->where('external_id', 'resend:'.$original->id)
            ->first();

        $this->assertNotNull($resend, 'El reenvío debe crear una fila de log trazable al original.');
        $this->assertSame('HelpdeskEmailLog', $resend->module);
        $this->assertSame(EmailLog::class, $resend->entity_type);
        $this->assertSame($original->id, (int) $resend->entity_id);
        $this->assertSame('Recibo #42', $resend->subject);
    }

    public function test_bulk_destroy_rejects_non_uuid_values(): void
    {
        EmailLog::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('helpdeskemaillog.bulk-destroy'), ['uids' => ['not-a-uuid']])
            ->assertSessionHasErrors('uids.0');
    }

    public function test_export_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskemaillog.export'))
            ->assertForbidden();
    }

    public function test_index_search_finds_by_recipient(): void
    {
        EmailLog::factory()->create([
            'subject' => 'Welcome aboard',
            'to_addresses' => ['needle@example.test'],
        ]);
        EmailLog::factory()->create([
            'subject' => 'Other message',
            'to_addresses' => ['other@example.test'],
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'needle@example.test']))
            ->assertOk()
            ->assertSee('Welcome aboard')
            ->assertDontSee('Other message');
    }

    public function test_resend_to_alternative_address_dispatches_job_with_override(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => ['orig@example.test']]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid), ['to' => 'alt@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(
            ResendEmailLogJob::class,
            fn (ResendEmailLogJob $job) => $job->emailLogId === $log->id && $job->overrideTo === 'alt@example.test',
        );
    }

    public function test_resend_rejects_invalid_alternative_address(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => ['orig@example.test']]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.resend', $log->uid), ['to' => 'not-an-email'])
            ->assertSessionHasErrors('to');

        Queue::assertNothingPushed();
    }

    public function test_manager_can_bulk_resend_logs(): void
    {
        Queue::fake();

        $logs = EmailLog::factory()->count(3)->create(['to_addresses' => ['dest@example.test']]);
        $noRecipient = EmailLog::factory()->create(['to_addresses' => []]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.bulk-resend'), [
                'uids' => $logs->push($noRecipient)->pluck('uid')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Solo los que tienen destinatario se encolan (3, no el de lista vacía).
        Queue::assertPushed(ResendEmailLogJob::class, 3);
    }

    public function test_bulk_resend_requires_manage_permission(): void
    {
        Queue::fake();

        $log = EmailLog::factory()->create(['to_addresses' => ['dest@example.test']]);

        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.bulk-resend'), ['uids' => [$log->uid]])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_download_returns_html_attachment(): void
    {
        $log = EmailLog::factory()->create([
            'body_html' => '<h1>Recibo de compra</h1>',
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.download', $log->uid));

        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Recibo de compra', $response->getContent());
    }

    public function test_download_returns_404_without_body(): void
    {
        $log = EmailLog::factory()->create(['body_html' => null, 'body_text' => null]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.download', $log->uid))
            ->assertNotFound();
    }

    public function test_download_requires_view_permission(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<p>hi</p>']);

        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskemaillog.download', $log->uid))
            ->assertForbidden();
    }

    public function test_manager_can_purge_body(): void
    {
        $log = EmailLog::factory()->create([
            'body_html' => '<p>Contenido sensible</p>',
            'body_text' => 'Contenido sensible',
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemaillog.purge-body', $log->uid))
            ->assertRedirect()
            ->assertSessionHas('success');

        $log->refresh();
        $this->assertNull($log->body_html);
        $this->assertNull($log->body_text);
        $this->assertTrue($log->metadata['redacted'] ?? false);
    }

    public function test_purge_body_requires_manage_permission(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<p>x</p>']);

        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.purge-body', $log->uid))
            ->assertForbidden();

        $this->assertNotNull($log->fresh()->body_html);
    }

    public function test_show_includes_related_emails_by_recipient(): void
    {
        // Direcciones únicas por corrida (no literales 'same@example.test' /
        // 'other@example.test') — este entorno comparte la BD de test entre
        // varias sesiones en paralelo (worktrees distintos, misma BD física);
        // con un literal fijo, la relación query (limit 8, sin desempate)
        // puede pisar 'Related one' con filas de OTRA corrida que compartan
        // exactamente el mismo destinatario. Confirmado en vivo: 15 filas
        // reales con to_addresses=['same@example.test'] y subject='Primary'
        // de corridas pasadas, suficientes para sacar 'Related one' del top-8.
        $recipient = Str::uuid()->toString().'@example.test';

        $log = EmailLog::factory()->create(['to_addresses' => [$recipient], 'subject' => 'Primary']);
        EmailLog::factory()->create(['to_addresses' => [$recipient], 'subject' => 'Related one']);
        EmailLog::factory()->create(['to_addresses' => ['other-'.$recipient], 'subject' => 'Unrelated']);
        // Contiene $recipient como substring: NO debe considerarse relacionado.
        EmailLog::factory()->create(['to_addresses' => ['not'.$recipient], 'subject' => 'Substring trap']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewHas('related', function ($related) {
                $subjects = $related->pluck('subject');

                return $subjects->contains('Related one')
                    && ! $subjects->contains('Unrelated')
                    && ! $subjects->contains('Substring trap')
                    && ! $subjects->contains('Primary');
            });
    }

    /**
     * Ventana por defecto (sin date_from/date_to): 14 días actuales vs los 14
     * anteriores (misma que usa el gráfico de tendencia). Se ancla a un año
     * lejano (2024) para no chocar con filas residuales reales de la BD
     * compartida, cuyo created_at cae siempre cerca del "ahora" real de cada
     * corrida — con el reloj congelado aquí, el baseline es de verdad 0.
     */
    public function test_index_computes_stats_delta_for_default_two_week_window(): void
    {
        $this->travelTo(Carbon::create(2024, 6, 15, 0, 0, 0));

        // Ventana actual: 2024-06-02 00:00:00 .. 2024-06-15 00:00:00 (ahora).
        EmailLog::factory()->count(2)->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2024, 6, 10, 12),
        ]);
        EmailLog::factory()->failed()->create(['created_at' => Carbon::create(2024, 6, 11, 12)]);

        // Ventana anterior: 2024-05-19 23:59:59 .. 2024-06-01 23:59:59.
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2024, 5, 25, 12),
        ]);

        // Fuera de ambas ventanas: no debe contarse en ningún lado.
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2024, 5, 1, 12),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewHas('statsDelta', function ($delta) {
                $total = $delta['total'];
                $sent = $delta['sent'];
                $failed = $delta['failed'];

                return $total === ['current' => 3, 'previous' => 1, 'diff' => 2, 'diff_percent' => 200.0, 'direction' => 'up', 'positive' => null]
                    && $sent === ['current' => 2, 'previous' => 1, 'diff' => 1, 'diff_percent' => 100.0, 'direction' => 'up', 'positive' => true]
                    && $failed === ['current' => 1, 'previous' => 0, 'diff' => 1, 'diff_percent' => null, 'direction' => 'up', 'positive' => false];
            });
    }

    /**
     * delivery_rate combina sent/total de cada periodo — se comprueba aparte
     * de los conteos crudos porque su polaridad (higherIsBetter) se evalúa
     * sobre un valor derivado, no sobre un COUNT directo.
     */
    public function test_index_computes_delivery_rate_delta(): void
    {
        $this->travelTo(Carbon::create(2024, 6, 15, 0, 0, 0));

        // Actual: 2 sent + 1 failed => delivery_rate = 2/3*100 = 66.7.
        EmailLog::factory()->count(2)->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2024, 6, 10, 12),
        ]);
        EmailLog::factory()->failed()->create(['created_at' => Carbon::create(2024, 6, 11, 12)]);

        // Anterior: 1 sent, 0 failed => delivery_rate = 100.0.
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2024, 5, 25, 12),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewHas('statsDelta', function ($delta) {
                $rate = $delta['delivery_rate'];

                return $rate['current'] === 66.7
                    && $rate['previous'] === 100.0
                    && $rate['diff'] === -33.3
                    && $rate['direction'] === 'down'
                    // Tasa de entrega cayendo: aunque "down" en abstracto no
                    // dice nada, aquí sí es una mala noticia (higherIsBetter).
                    && $rate['positive'] === false;
            });
    }

    /**
     * Con date_from/date_to activos, el periodo anterior es la ventana
     * inmediatamente antes de igual duración — se usa un rango en 2020 (lejos
     * del "ahora" real) para que el resultado sea 100% determinista sin
     * necesidad de congelar el reloj.
     */
    public function test_index_computes_stats_delta_for_active_date_filter(): void
    {
        // Actual: 2020-01-10 00:00:00 .. 2020-01-16 23:59:59 (7 días).
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2020, 1, 12, 12),
        ]);

        // Anterior (misma duración, justo antes): 2020-01-03 00:00:00 .. 2020-01-09 23:59:59.
        EmailLog::factory()->count(3)->create([
            'status' => EmailStatus::Sent,
            'created_at' => Carbon::create(2020, 1, 6, 12),
        ]);

        // Fuera de ambas ventanas.
        EmailLog::factory()->create(['created_at' => Carbon::create(2020, 1, 20, 12)]);
        EmailLog::factory()->create(['created_at' => Carbon::create(2020, 1, 1, 12)]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['date_from' => '2020-01-10', 'date_to' => '2020-01-16']))
            ->assertOk()
            ->assertViewHas('statsDelta', function ($delta) {
                $total = $delta['total'];

                return $total['current'] === 1
                    && $total['previous'] === 3
                    && $total['diff'] === -2
                    && $total['direction'] === 'down';
            });
    }

    /**
     * Sin envíos con seguimiento en el periodo anterior: la tasa no tiene
     * denominador (rateFrom() devuelve null), así que el delta no puede
     * calcularse — se preserva el valor actual pero sin diff/dirección real,
     * en vez de fingir un "0%" que insinuaría un dato que no existe.
     */
    public function test_index_open_rate_delta_has_no_data_for_previous_period_without_tracking(): void
    {
        $tracked = EmailLog::factory()->tracked()->create(['created_at' => Carbon::create(2020, 3, 12, 12)]);
        EmailLogOpen::create([
            'email_log_id' => $tracked->id,
            'ip' => '203.0.113.10',
            'user_agent' => 'test',
            'opened_at' => Carbon::create(2020, 3, 12, 12),
        ]);

        // Periodo anterior sin ningún envío con seguimiento activado.
        EmailLog::factory()->create(['created_at' => Carbon::create(2020, 3, 4, 12)]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['date_from' => '2020-03-10', 'date_to' => '2020-03-16']))
            ->assertOk()
            ->assertViewHas('statsDelta', function ($delta) {
                $rate = $delta['open_rate'];

                return $rate['current'] === 100.0
                    && $rate['previous'] === null
                    && $rate['diff'] === null
                    && $rate['direction'] === 'flat'
                    && $rate['positive'] === null;
            });
    }

    public function test_export_selected_returns_csv_for_chosen_uids_only(): void
    {
        $selected = EmailLog::factory()->create(['subject' => 'Selected export subject']);
        $notSelected = EmailLog::factory()->create(['subject' => 'Not selected export subject']);

        $response = $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.export-selected'), ['uids' => [$selected->uid]]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Selected export subject', $content);
        $this->assertStringNotContainsString('Not selected export subject', $content);
    }

    public function test_export_selected_requires_view_permission(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('helpdeskemaillog.export-selected'), ['uids' => [$log->uid]])
            ->assertForbidden();
    }

    public function test_export_selected_validates_empty_selection(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.export-selected'), ['uids' => []])
            ->assertSessionHasErrors('uids');
    }

    public function test_export_selected_rejects_non_uuid_values(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('helpdeskemaillog.export-selected'), ['uids' => ['not-a-uuid']])
            ->assertSessionHasErrors('uids.0');
    }

    /**
     * La posición/prev/next se calculan sobre el listado FILTRADO completo
     * (12 filas), no sobre la página actual (per_page=10 deja al objetivo en
     * la página 2) — aislado por módulo único para no arrastrar filas
     * residuales de la BD compartida.
     */
    public function test_show_exposes_position_and_prev_next_navigation_independent_of_pagination(): void
    {
        $module = 'NavPage-'.Str::random(8);

        $logs = collect(range(0, 11))->map(fn (int $i) => EmailLog::factory()->forModule($module)->create([
            'subject' => sprintf('Nav subject %02d', $i),
        ]));

        // Orden ascendente por subject: 'Nav subject 00' es el primero, 'Nav
        // subject 11' el último. El objetivo (índice 10) es la posición 11/12.
        $target = $logs[10];

        $response = $this->actingAs($this->viewer())->get(route('helpdeskemaillog.show', [
            'emailLog' => $target->uid,
            'module' => $module,
            'sort_by' => 'subject',
            'sort_dir' => 'asc',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertViewHas('selectedPosition', 11)
            ->assertViewHas('selectedTotal', 12)
            ->assertViewHas('prevUid', $logs[9]->uid)
            ->assertViewHas('nextUid', $logs[11]->uid);
    }

    public function test_show_prev_and_next_are_null_when_it_is_the_only_matching_log(): void
    {
        $module = 'SoloModule-'.Str::random(8);
        $log = EmailLog::factory()->forModule($module)->create(['subject' => 'Solo subject']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', ['emailLog' => $log->uid, 'module' => $module]))
            ->assertOk()
            ->assertViewHas('selectedPosition', 1)
            ->assertViewHas('selectedTotal', 1)
            ->assertViewHas('prevUid', fn ($uid) => $uid === null)
            ->assertViewHas('nextUid', fn ($uid) => $uid === null);
    }

    /**
     * Sin ninguna fila que autoseleccionar (filtro sin resultados): todos los
     * valores de navegación deben quedar en null/0, nunca romper el render.
     */
    public function test_index_navigation_is_null_without_any_matching_log(): void
    {
        $module = 'EmptyModule-'.Str::random(8);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['module' => $module]))
            ->assertOk()
            ->assertViewHas('log', fn ($log) => $log === null)
            ->assertViewHas('selectedPosition', fn ($position) => $position === null)
            ->assertViewHas('selectedTotal', 0)
            ->assertViewHas('prevUid', fn ($uid) => $uid === null)
            ->assertViewHas('nextUid', fn ($uid) => $uid === null);
    }

    /**
     * El extracto se limpia a una sola línea (sin saltos ni espacios
     * repetidos) y se corta corto — nunca se carga el cuerpo completo para
     * esto (ver EmailLog::LIST_COLUMNS/bodySnippet()).
     */
    public function test_index_exposes_a_plain_text_body_snippet_per_row(): void
    {
        $log = EmailLog::factory()->create([
            'subject' => 'Snippet source subject',
            'body_text' => "Hola  Gabriel,\n\nHemos   actualizado tu pedido con éxito y ya está en camino hacia tu domicilio sin más incidencias que reportar hoy.",
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'Snippet source subject']))
            ->assertOk()
            ->assertViewHas('logs', function ($logs) use ($log) {
                $row = $logs->firstWhere('uid', $log->uid);

                return $row !== null
                    && $row->body_snippet !== null
                    && ! str_contains($row->body_snippet, "\n")
                    && str_starts_with($row->body_snippet, 'Hola Gabriel, Hemos actualizado tu pedido');
            });
    }

    /**
     * Un envío redactado/purgado siempre tiene body_text en null (ver
     * bodyOf()/purgeBody()) — "sin extracto" y "sin cuerpo" son la misma
     * condición aquí, sin caso especial adicional.
     */
    public function test_index_body_snippet_is_empty_when_the_body_was_redacted(): void
    {
        $log = EmailLog::factory()->create([
            'subject' => 'Redacted snippet subject',
            'body_html' => null,
            'body_text' => null,
            'metadata' => ['redacted' => true],
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'Redacted snippet subject']))
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->firstWhere('uid', $log->uid)?->body_snippet === null);
    }

    public function test_show_computes_recipient_aggregates_for_the_sidebar_card(): void
    {
        $this->travelTo(Carbon::create(2024, 7, 1, 12, 0, 0));

        $recipient = Str::uuid()->toString().'@example.test';

        $opened = EmailLog::factory()->create([
            'to_addresses' => [$recipient],
            'status' => EmailStatus::Sent,
            'subject' => 'Recipient stats one',
        ]);
        $target = EmailLog::factory()->create([
            'to_addresses' => [$recipient],
            'status' => EmailStatus::Sent,
            'subject' => 'Recipient stats two',
        ]);
        EmailLog::factory()->failed()->create([
            'to_addresses' => [$recipient],
            'subject' => 'Recipient stats three',
        ]);

        EmailLogOpen::create([
            'email_log_id' => $opened->id,
            'ip' => '203.0.113.20',
            'user_agent' => 'test',
            'opened_at' => Carbon::create(2024, 6, 30, 9, 0, 0),
        ]);
        $lastOpen = Carbon::create(2024, 6, 30, 18, 30, 0);
        EmailLogOpen::create([
            'email_log_id' => $target->id,
            'ip' => '203.0.113.21',
            'user_agent' => 'test',
            'opened_at' => $lastOpen,
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $target->uid))
            ->assertOk()
            // 2 sent + 1 failed de 3 recibidos => delivery_rate = 2/3*100 = 66.7
            // (mismo criterio que el KPI global 'delivery_rate', ver rateFrom()).
            ->assertViewHas('recipientStats', function ($stats) use ($recipient, $lastOpen) {
                return $stats['email'] === $recipient
                    && $stats['name'] === null
                    && $stats['company'] === null
                    && $stats['total_received'] === 3
                    && $stats['delivery_rate'] === 66.7
                    && $stats['last_opened_at']->equalTo($lastOpen);
            });
    }

    public function test_show_recipient_stats_is_null_without_any_recipient(): void
    {
        $log = EmailLog::factory()->create(['to_addresses' => []]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewHas('recipientStats', fn ($stats) => $stats === null);
    }
}
