<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

class EmailLogControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

    public function test_show_displays_the_email_preview(): void
    {
        $log = EmailLog::factory()->create(['subject' => 'Order confirmation']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', $log->uid))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.preview')
            ->assertSee('Order confirmation');
    }

    public function test_show_returns_404_for_unknown_uid(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.show', Str::orderedUuid()))
            ->assertNotFound();
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
        $log = EmailLog::factory()->create(['to_addresses' => ['same@example.test'], 'subject' => 'Primary']);
        EmailLog::factory()->create(['to_addresses' => ['same@example.test'], 'subject' => 'Related one']);
        EmailLog::factory()->create(['to_addresses' => ['other@example.test'], 'subject' => 'Unrelated']);
        // Contiene 'same@example.test' como substring: NO debe considerarse relacionado.
        EmailLog::factory()->create(['to_addresses' => ['notsame@example.test'], 'subject' => 'Substring trap']);

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
}
