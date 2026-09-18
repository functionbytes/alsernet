<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogOpen;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;
use Modules\HelpdeskEmailActivity\Services\EmailDeliveryLookupService;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * API de consulta de entregabilidad: la pieza que usan otros módulos para
 * responder "¿se le envió a este cliente?, ¿lo abrió?".
 */
class EmailDeliveryLookupTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $admin;

    private EmailDeliveryLookupService $lookup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-settings');

        $this->lookup = app(EmailDeliveryLookupService::class);
    }

    public function test_dice_si_a_un_destinatario_se_le_envio_y_si_lo_abrio(): void
    {
        $log = $this->log('ana@ejemplo.test', 'DemoModule');
        $this->open($log);

        $result = $this->lookup->forRecipient('ana@ejemplo.test', 'DemoModule');

        $this->assertTrue($result['was_sent']);
        $this->assertTrue($result['was_opened']);
        $this->assertFalse($result['was_clicked']);
        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $result['opened']);
    }

    public function test_un_correo_sin_abrir_se_reporta_como_no_abierto(): void
    {
        $this->log('luis@ejemplo.test', 'DemoModule');

        $result = $this->lookup->forRecipient('luis@ejemplo.test', 'DemoModule');

        $this->assertTrue($result['was_sent']);
        $this->assertFalse($result['was_opened']);
    }

    public function test_el_modulo_acota_la_respuesta(): void
    {
        $log = $this->log('ana@ejemplo.test', 'OtroModulo');
        $this->open($log);

        // Preguntando por un módulo distinto, ese correo no cuenta.
        $result = $this->lookup->forRecipient('ana@ejemplo.test', 'DemoModule');

        $this->assertFalse($result['was_sent']);
        $this->assertFalse($result['was_opened']);
    }

    public function test_marca_a_quien_esta_en_la_lista_de_supresion(): void
    {
        EmailSuppression::create([
            'email' => 'baja@ejemplo.test',
            'module' => '',
            'reason' => 'unsubscribed',
        ]);

        $result = $this->lookup->forRecipient('baja@ejemplo.test');

        $this->assertTrue($result['suppressed']);
    }

    public function test_la_consulta_en_lote_devuelve_una_entrada_por_destinatario(): void
    {
        $log = $this->log('ana@ejemplo.test', 'DemoModule');
        $this->open($log);
        $this->log('luis@ejemplo.test', 'DemoModule');

        $result = $this->lookup->forRecipients(
            ['ana@ejemplo.test', 'luis@ejemplo.test', 'nadie@ejemplo.test'],
            'DemoModule'
        );

        $this->assertCount(3, $result);
        $this->assertTrue($result['ana@ejemplo.test']['was_opened']);
        $this->assertFalse($result['luis@ejemplo.test']['was_opened']);
        $this->assertTrue($result['luis@ejemplo.test']['was_sent']);
        // A quien nunca se le escribió no aparece como enviado, pero sí sale
        // en el resultado: el listado que la consume necesita la fila igual.
        $this->assertFalse($result['nadie@ejemplo.test']['was_sent']);
    }

    public function test_las_estadisticas_de_modulo_calculan_las_tasas(): void
    {
        $opened = $this->log('ana@ejemplo.test', 'DemoModule');
        $this->open($opened);
        $this->log('luis@ejemplo.test', 'DemoModule');

        $stats = $this->lookup->statsForModule('DemoModule');

        $this->assertSame(2, $stats['sent']);
        $this->assertSame(1, $stats['opened']);
        $this->assertSame(50.0, $stats['open_rate']);
        $this->assertSame(0.0, $stats['click_rate']);
    }

    public function test_consulta_por_entidad(): void
    {
        $this->log('ana@ejemplo.test', 'DemoModule', entityType: 'App\\Demo', entityId: 42);

        $result = $this->lookup->forEntity('App\\Demo', 42, 'DemoModule');

        $this->assertTrue($result['was_sent']);
        $this->assertSame(42, $result['entity_id']);
    }

    /* ── Endpoints HTTP ──────────────────────────────────────────────────── */

    public function test_el_endpoint_de_destinatario_responde_json(): void
    {
        $log = $this->log('ana@ejemplo.test', 'DemoModule');
        $this->open($log);

        $this->actingAs($this->admin)
            ->getJson(route('helpdeskemailactivity.lookup.recipient', [
                'email' => 'ana@ejemplo.test',
                'module' => 'DemoModule',
                'history' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.was_sent', true)
            ->assertJsonPath('data.was_opened', true)
            ->assertJsonCount(1, 'data.history');
    }

    public function test_el_endpoint_en_lote_acepta_una_lista_de_correos(): void
    {
        $this->log('ana@ejemplo.test', 'DemoModule');

        $response = $this->actingAs($this->admin)
            ->postJson(route('helpdeskemailactivity.lookup.recipients'), [
                'emails' => ['ana@ejemplo.test', 'luis@ejemplo.test'],
                'module' => 'DemoModule',
            ])
            ->assertOk();

        $data = $response->json('data');

        $this->assertTrue($data['ana@ejemplo.test']['was_sent']);
        $this->assertFalse($data['luis@ejemplo.test']['was_sent']);
    }

    public function test_el_endpoint_de_estadisticas_por_modulo_responde(): void
    {
        $this->log('ana@ejemplo.test', 'DemoModule');

        $this->actingAs($this->admin)
            ->getJson(route('helpdeskemailactivity.lookup.module-stats', ['emailModule' => 'DemoModule']))
            ->assertOk()
            ->assertJsonPath('data.module', 'DemoModule')
            ->assertJsonPath('data.sent', 1);
    }

    public function test_un_email_invalido_se_rechaza(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('helpdeskemailactivity.lookup.recipient', ['email' => 'no-es-un-email']))
            ->assertStatus(422);
    }

    public function test_sin_permiso_no_se_puede_consultar(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('helpdeskemailactivity.lookup.recipient', ['email' => 'ana@ejemplo.test']))
            ->assertForbidden();
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function log(string $email, string $module, ?string $entityType = null, int|string|null $entityId = null): EmailLog
    {
        return EmailLog::create([
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from_address' => 'no-reply@ejemplo.test',
            'to_addresses' => [$email],
            'subject' => 'Asunto de prueba',
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);
    }

    private function open(EmailLog $log): void
    {
        EmailLogOpen::create([
            'email_log_id' => $log->id,
            'opened_at' => now(),
        ]);
    }
}
