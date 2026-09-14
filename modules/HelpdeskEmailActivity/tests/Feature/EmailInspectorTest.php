<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Models\EmailLinkCheck;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\EmailHtmlCheckService;
use Modules\HelpdeskEmailActivity\Support\CanIEmailDataset;
use Modules\HelpdeskEmailActivity\Support\EmailMessageAssembler;
use Tests\TestCase;

/**
 * Inspector de mensaje: las dos rutas AJAX (compatibilidad y enlaces) y el
 * ensamblador que alimenta las pestañas "Cabeceras" y "Original".
 */
class EmailInspectorTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app,
    // no en mariadb/helpdesk — sin declararla, cada factory()->create() de este
    // archivo dejaría filas reales sin rollback (mismo gotcha que el resto de
    // tests del módulo, ver EmailLogControllerTest).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');
    }

    public function test_html_check_requires_view_permission(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<p style="margin:0">a</p>']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
            ->assertForbidden();
    }

    public function test_html_check_returns_warnings_and_score(): void
    {
        $log = EmailLog::factory()->create([
            'body_html' => '<div style="margin:0; border-radius:8px">Hola</div>',
        ]);

        Cache::forget("helpdeskemailactivity:html-check:{$log->uid}");

        $response = $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonStructure([
                'Warnings' => [['Slug', 'Title', 'Description', 'Category', 'Results', 'Score']],
                'Platforms',
                'PlatformNames',
                'Total' => ['Tests', 'Nodes', 'Supported', 'Partial', 'Unsupported'],
            ]);

        $slugs = array_column($response->json('Warnings'), 'Slug');

        $this->assertContains('css-margin', $slugs);
        $this->assertContains('css-border-radius', $slugs);
    }

    public function test_html_check_distinguishes_no_html_from_discarded_html(): void
    {
        $viewer = $this->viewer();

        $noHtml = EmailLog::factory()->create([
            'body_html' => null,
            'metadata' => ['has_html' => false],
        ]);

        $discarded = EmailLog::factory()->create([
            'body_html' => null,
            // El correo SÍ llevaba HTML: no se guardó porque el ajuste
            // "Guardar cuerpo" estaba desactivado al enviarlo.
            'metadata' => ['has_html' => true],
        ]);

        $first = $this->actingAs($viewer)
            ->getJson(route('helpdeskemailactivity.html-check', $noHtml->uid))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->json('reason');

        $second = $this->actingAs($viewer)
            ->getJson(route('helpdeskemailactivity.html-check', $discarded->uid))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->json('reason');

        $this->assertNotSame($first, $second);
    }

    public function test_html_check_result_is_cached_per_log(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<div style="margin:0">a</div>']);
        $key = EmailHtmlCheckService::cacheKey($log->uid);

        Cache::forget($key);

        $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
            ->assertOk();

        $this->assertTrue(Cache::has($key));

        Cache::forget($key);
    }

    public function test_purging_the_body_invalidates_the_cached_analysis(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<div style="margin:0">a</div>']);
        $key = EmailHtmlCheckService::cacheKey($log->uid);

        $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
            ->assertOk();

        $this->assertTrue(Cache::has($key));

        // Seguir sirviendo el informe de un cuerpo ya purgado describiría algo
        // que ya no existe en el registro.
        $log->update(['body_html' => null]);

        $this->assertFalse(Cache::has($key));
    }

    public function test_link_check_skips_own_tracking_links(): void
    {
        $log = EmailLog::factory()->create();

        // El píxel de apertura del propio módulo: se lista, pero no se pide.
        $log->forceFill([
            'body_html' => '<img src="https://panel.test/e/'.$log->uid.'.gif">',
        ])->save();

        $this->actingAs($this->viewer())
            ->postJson(route('helpdeskemailactivity.link-check', $log->uid))
            ->assertOk()
            ->assertJsonPath('Skipped', 1)
            ->assertJsonPath('Errors', 0);
    }

    public function test_link_check_requires_view_permission(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<p>a</p>']);

        $this->actingAs(User::factory()->create())
            ->postJson(route('helpdeskemailactivity.link-check', $log->uid))
            ->assertForbidden();
    }

    public function test_link_check_stores_every_checked_url(): void
    {
        $log = EmailLog::factory()->create();

        $log->forceFill([
            'body_html' => '<img src="https://panel.test/e/'.$log->uid.'.gif">',
        ])->save();

        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->postJson(route('helpdeskemailactivity.link-check', $log->uid))
            ->assertOk();

        $stored = EmailLinkCheck::where('email_log_id', $log->id)->get();

        $this->assertCount(1, $stored);
        $this->assertSame($viewer->id, $stored->first()->checked_by);
        $this->assertNotNull($stored->first()->checked_at);
    }

    public function test_link_check_history_returns_the_last_run_only(): void
    {
        $log = EmailLog::factory()->create();

        // Dos ejecuciones: la vieja decía que el enlace estaba bien, la nueva
        // que no. El panel debe enseñar la nueva, no mezclarlas.
        EmailLinkCheck::insert([
            [
                'email_log_id' => $log->id,
                'url' => 'https://example.com/a',
                'url_hash' => EmailLinkCheck::hashUrl('https://example.com/a'),
                'status_code' => 200,
                'status' => 'OK',
                'checked_by' => null,
                'checked_at' => now()->subDay(),
            ],
            [
                'email_log_id' => $log->id,
                'url' => 'https://example.com/a',
                'url_hash' => EmailLinkCheck::hashUrl('https://example.com/a'),
                'status_code' => 404,
                'status' => 'Not Found',
                'checked_by' => null,
                'checked_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.link-check-history', $log->uid))
            ->assertOk()
            ->assertJsonCount(1, 'Links')
            ->assertJsonPath('Links.0.StatusCode', 404)
            ->assertJsonPath('Errors', 1);

        $this->assertNotNull($response->json('CheckedAt'));
    }

    public function test_link_check_history_is_empty_when_never_checked(): void
    {
        $log = EmailLog::factory()->create();

        $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.link-check-history', $log->uid))
            ->assertOk()
            ->assertJsonPath('Links', [])
            ->assertJsonPath('CheckedAt', null);
    }

    public function test_a_trashed_log_can_still_be_inspected_but_read_only(): void
    {
        $log = EmailLog::factory()->create(['body_html' => '<p>hola</p>']);
        $log->delete();

        $manager = tap(User::factory()->create())
            ->givePermissionTo(['helpdeskemailactivity.view', 'helpdeskemailactivity.manage']);

        $response = $this->actingAs($manager)
            ->get(route('helpdeskemailactivity.show', $log->uid))
            ->assertOk();

        // Se ve el contenido y el aviso, pero ninguna acción que actúe sobre un
        // registro que alguien ya decidió borrar (reenviar mandaría un correo
        // real).
        $response->assertSee('papelera', false);
        $response->assertDontSee(route('helpdeskemailactivity.resend', $log->uid), false);
    }

    public function test_remote_stylesheets_are_not_downloaded_by_default(): void
    {
        // Analizar un correo no debe generar tráfico saliente salvo que se
        // active explícitamente (ver config html_check_remote_css).
        Http::preventStrayRequests();

        $log = EmailLog::factory()->create([
            'body_html' => '<html><head><link rel="stylesheet" href="https://cdn.example.com/mail.css"></head><body><p style="margin:0">a</p></body></html>',
        ]);

        Cache::forget(EmailHtmlCheckService::cacheKey($log->uid));

        $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
            ->assertOk()
            ->assertJsonPath('available', true);
    }

    public function test_remote_stylesheets_count_when_enabled(): void
    {
        // OutboundUrlGuard resuelve el host de verdad antes de dejar salir la
        // petición, así que la URL de prueba tiene que apuntar a un dominio que
        // resuelva. example.com está reservado por la IANA justo para esto; sin
        // DNS el test no tiene nada que comprobar.
        if (gethostbynamel('example.com') === false) {
            $this->markTestSkipped('Sin resolución DNS: OutboundUrlGuard bloquearía la descarga.');
        }

        config(['helpdeskemailactivity.html_check_remote_css' => true]);

        Http::fake([
            'example.com/*' => Http::response('p { text-shadow: 1px 1px 2px #000; }', 200, ['Content-Type' => 'text/css']),
        ]);

        $log = EmailLog::factory()->create([
            'body_html' => '<html><head><link rel="stylesheet" href="https://example.com/mail.css"></head><body><p>a</p></body></html>',
        ]);

        Cache::forget(EmailHtmlCheckService::cacheKey($log->uid));

        $slugs = array_column(
            $this->actingAs($this->viewer())
                ->getJson(route('helpdeskemailactivity.html-check', $log->uid))
                ->assertOk()
                ->json('Warnings'),
            'Slug',
        );

        // La propiedad solo existe en la hoja remota: si aparece, se descargó
        // y se fundió en el nodo antes de analizar.
        $this->assertContains('css-text-shadow', $slugs);
    }

    public function test_cache_key_changes_with_the_dataset_version(): void
    {
        // Al actualizar el dataset de caniemail las puntuaciones pueden cambiar;
        // si la clave no cambiara con él, los informes ya calculados seguirían
        // sirviéndose con los datos viejos hasta que expirase su TTL.
        $before = EmailHtmlCheckService::cacheKey('uid-fijo');

        $path = CanIEmailDataset::path();
        $backup = File::get($path);

        try {
            $data = json_decode($backup, true);
            $data['last_update_date'] = '2099-12-31 00:00:00 +0000';
            File::put($path, json_encode($data));
            CanIEmailDataset::flush();

            $this->assertNotSame($before, EmailHtmlCheckService::cacheKey('uid-fijo'));
        } finally {
            File::put($path, $backup);
            CanIEmailDataset::flush();
        }
    }

    public function test_assembler_splits_headers_keeping_folded_lines(): void
    {
        $log = EmailLog::factory()->make([
            'raw_headers' => "From: a@b.c\r\nReceived: from uno\r\n\tby dos\r\nSubject: hola\r\n",
        ]);

        $headers = collect(EmailMessageAssembler::headers($log))->keyBy('name');

        $this->assertSame('a@b.c', $headers['From']['value']);
        // La continuación (línea que empieza por espacio o tabulador) pertenece
        // a la cabecera anterior, no es una cabecera nueva.
        $this->assertSame('from uno by dos', $headers['Received']['value']);
        $this->assertCount(3, $headers);
    }

    public function test_assembler_groups_repeated_headers(): void
    {
        $log = EmailLog::factory()->make([
            'raw_headers' => "Received: por A\r\nReceived: por B\r\nFrom: a@b.c\r\n",
        ]);

        $headers = collect(EmailMessageAssembler::headers($log))->keyBy('name');

        $this->assertCount(2, $headers);
        $this->assertSame("por A\npor B", $headers['Received']['value']);
    }

    public function test_assembler_marks_reconstructed_headers(): void
    {
        $log = EmailLog::factory()->make([
            'raw_headers' => null,
            'subject' => 'Asunto',
            'from_address' => 'a@b.c',
            'to_addresses' => ['d@e.f'],
        ]);

        $eml = EmailMessageAssembler::eml($log);

        // Quien lea el .eml debe saber que no es una copia literal del envío.
        $this->assertStringContainsString('Cabeceras reconstruidas', $eml);
        $this->assertStringContainsString('Subject: Asunto', $eml);
    }
}
