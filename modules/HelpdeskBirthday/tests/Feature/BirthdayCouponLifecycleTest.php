<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\Erp\Services\ErpService;
use Modules\HelpdeskBirthday\Jobs\SendBirthdayEmailJob;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * El ciclo del bono de principio a fin: que Gestión lo emita al preparar la
 * campaña, que nadie se quede en un bucle si no lo emite, y que a un cumpleaños
 * pasado no se le mande nada.
 *
 * Los tres agujeros que cubre esta clase estuvieron abiertos a la vez:
 * `generateFor()` no lo llamaba nadie, así que ningún destinatario tenía código;
 * el job devolvía a la cola a quien no lo tenía, con lo que dispatch-due lo
 * reservaba otra vez al minuto siguiente, indefinidamente; y una campaña sin
 * terminar seguía viva días después.
 */
class BirthdayCouponLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk', 'mysql', 'mariadb'];

    private CarbonImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha lejana: campaign_date es UNIQUE y el scheduler crea una campaña
        // real cada día.
        $this->date = CarbonImmutable::parse('2029-11-19');

        config()->set('helpdeskbirthday.customers_api_url', 'http://manager.test');
        config()->set('helpdeskbirthday.audience_source', 'api');
        config()->set('helpdeskbirthday.max_recipients', 2000);

        $this->app->instance(BirthdaySettings::class, $this->settings());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_al_preparar_la_campana_gestion_emite_un_bono_por_cliente(): void
    {
        $this->fakeManager([
            $this->customer(11, 'ana@ejemplo.test', 'Ana'),
            $this->customer(22, 'luis@ejemplo.test', 'Luis'),
        ]);

        $this->fakeErp(bonos: [
            11 => ['idbono_promocion' => '900001', 'codigo_verificacion' => 'AAA', 'importe' => '5.00'],
            22 => ['idbono_promocion' => '900002', 'codigo_verificacion' => 'BBB', 'importe' => '5.00'],
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $ana = $campaign->recipients()->where('erp_customer_id', 11)->first();
        $luis = $campaign->recipients()->where('erp_customer_id', 22)->first();

        // Cada uno con SU bono: si se cruzaran, a alguien le llegaría el regalo
        // de otro.
        $this->assertSame('900001-AAA', $ana->publicCode());
        $this->assertSame('900002-BBB', $luis->publicCode());
        $this->assertSame(BirthdayCampaign::STATUS_SCHEDULED, $campaign->status);
    }

    public function test_a_quien_gestion_no_le_emite_bono_no_se_le_escribe(): void
    {
        $this->fakeManager([
            $this->customer(11, 'ana@ejemplo.test', 'Ana'),
            $this->customer(22, 'luis@ejemplo.test', 'Luis'),
        ]);

        // Gestión solo devuelve el bono de Ana.
        $this->fakeErp(bonos: [
            11 => ['idbono_promocion' => '900001', 'codigo_verificacion' => 'AAA', 'importe' => '5.00'],
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $luis = $campaign->recipients()->where('erp_customer_id', 22)->first();

        $this->assertSame(BirthdayRecipient::STATUS_SKIPPED, $luis->status);
        $this->assertSame(BirthdayRecipient::SKIP_NO_COUPON, $luis->skip_reason);
        // Apartado, no pendiente: pendiente volvía a encolarse cada minuto.
        $this->assertNull($luis->scheduled_at);
        $this->assertSame(1, $campaign->fresh()->skipped_count);
    }

    public function test_si_nadie_recibe_bono_la_campana_queda_en_pausa(): void
    {
        $this->fakeManager([$this->customer(11, 'ana@ejemplo.test', 'Ana')]);
        $this->fakeErp(bonos: []);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(BirthdayCampaign::STATUS_PAUSED, $campaign->status);
        $this->assertFalse($campaign->isActive());
        $this->assertStringContainsString('no emitió ningún bono', (string) $campaign->error_message);
    }

    public function test_el_despacho_no_encola_a_quien_no_tiene_bono(): void
    {
        Queue::fake();

        $campaign = $this->campaignWith([
            ['email' => 'con@ejemplo.test', 'coupon_code' => '900001', 'coupon_verification_code' => 'AAA'],
            ['email' => 'sin@ejemplo.test', 'coupon_code' => null],
        ]);

        $this->artisan('helpdeskbirthday:dispatch-due')->assertSuccessful();

        Queue::assertPushed(SendBirthdayEmailJob::class, 1);

        $sin = $campaign->recipients()->where('email', 'sin@ejemplo.test')->first();
        $this->assertSame(BirthdayRecipient::STATUS_PENDING, $sin->status, 'No se le reserva: se queda quieto, no en bucle.');
    }

    public function test_el_job_aparta_al_que_llega_sin_bono_en_vez_de_devolverlo_a_la_cola(): void
    {
        $campaign = $this->campaignWith([
            ['email' => 'sin@ejemplo.test', 'coupon_code' => null, 'status' => BirthdayRecipient::STATUS_SENDING],
        ]);

        $recipient = $campaign->recipients()->first();

        (new SendBirthdayEmailJob($recipient->id))->handle();

        $recipient->refresh();

        // La clave del arreglo: NO vuelve a 'pending'. Si volviera, dispatch-due
        // lo reservaría de nuevo y el ciclo no terminaría nunca.
        $this->assertSame(BirthdayRecipient::STATUS_SKIPPED, $recipient->status);
        $this->assertSame(BirthdayRecipient::SKIP_NO_COUPON, $recipient->skip_reason);
    }

    public function test_el_envio_queda_enlazado_con_el_log_de_correo(): void
    {
        $campaign = $this->campaignWith([
            ['email' => 'traza@ejemplo.test', 'coupon_code' => '900001', 'coupon_verification_code' => 'AAA', 'status' => BirthdayRecipient::STATUS_SENDING],
        ]);

        $recipient = $campaign->recipients()->first();

        (new SendBirthdayEmailJob($recipient->id))->handle();

        $recipient->refresh();

        $this->assertSame(BirthdayRecipient::STATUS_SENT, $recipient->status);

        // HelpdeskEmailActivity registra el envío por su cuenta (el Mailable
        // declara módulo y entidad), pero el enlace de vuelta es lo que permite
        // saltar a la trazabilidad desde la fila y enseñar el HTML que salió.
        $log = EmailLog::query()
            ->where('module', 'HelpdeskBirthday')
            ->where('entity_type', BirthdayRecipient::class)
            ->where('entity_id', $recipient->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'El envío no quedó registrado en el log de correo.');
        $this->assertSame((int) $log->id, (int) $recipient->email_log_id);
        $this->assertContains('traza@ejemplo.test', (array) $log->to_addresses);
    }

    public function test_una_campana_de_ayer_ya_no_envia_y_se_cierra(): void
    {
        $campaign = $this->campaignWith(
            [['email' => 'tarde@ejemplo.test', 'coupon_code' => '900001', 'coupon_verification_code' => 'AAA']],
            date: CarbonImmutable::today()->subDays(2),
        );

        $expired = app(BirthdayCampaignService::class)->expireIfOverdue($campaign);

        $this->assertSame(1, $expired);
        $this->assertSame(BirthdayCampaign::STATUS_COMPLETED, $campaign->fresh()->status);
        $this->assertSame(
            BirthdayRecipient::SKIP_EXPIRED,
            $campaign->recipients()->first()->skip_reason,
        );
    }

    public function test_la_campana_de_hoy_no_caduca(): void
    {
        $campaign = $this->campaignWith(
            [['email' => 'hoy@ejemplo.test', 'coupon_code' => '900001', 'coupon_verification_code' => 'AAA']],
            date: CarbonImmutable::today(),
        );

        $this->assertSame(0, app(BirthdayCampaignService::class)->expireIfOverdue($campaign));
        $this->assertTrue($campaign->fresh()->isActive());
    }

    public function test_un_fallo_del_erp_deja_la_campana_marcada_y_no_muda(): void
    {
        // Un timeout de la API de clientes: no es BirthdayAudienceException, y
        // antes subía sin capturar dejando la campaña en 'draft' para siempre.
        Http::fake([
            'manager.test/api/erp/customer*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(BirthdayCampaign::STATUS_FAILED, $campaign->status);
        $this->assertStringContainsString('timed out', (string) $campaign->error_message);
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function campaignWith(array $recipients, ?CarbonImmutable $date = null): BirthdayCampaign
    {
        $date ??= $this->date;

        BirthdayCampaign::query()->whereDate('campaign_date', $date->toDateString())->delete();

        // dispatch-due recorre TODAS las campañas activas, y en esta base hay
        // campañas reales del día en curso con destinatarios vencidos: sin esto
        // el comando encolaba también los suyos y las cuentas del test salían
        // infladas. Se pausan dentro de la transacción, así que la base real no
        // se entera.
        BirthdayCampaign::query()
            ->active()
            ->update(['status' => BirthdayCampaign::STATUS_PAUSED]);

        $campaign = BirthdayCampaign::query()->create([
            'campaign_date' => $date->toDateString(),
            'status' => BirthdayCampaign::STATUS_SCHEDULED,
            'template_key' => 'birthday-coupon',
            'window_start' => '09:00:00',
            'window_end' => '14:00:00',
            'throttle_per_hour' => 600,
            'interval_seconds' => 60,
            'recipients_total' => count($recipients),
        ]);

        foreach ($recipients as $row) {
            $campaign->recipients()->create($row + [
                'name' => 'Cliente',
                'erp_customer_id' => '1',
                'scheduled_at' => now()->subMinute(),
                'status' => BirthdayRecipient::STATUS_PENDING,
            ]);
        }

        return $campaign;
    }

    private function fakeManager(array $customers): void
    {
        Http::fake([
            'manager.test/api/erp/customer*' => Http::response([
                'success' => true,
                'data' => $customers,
                'pagination' => ['limit' => 100, 'offset' => 0, 'count' => count($customers), 'hasMore' => false],
            ]),
        ]);
    }

    private function customer(int $id, string $email, string $name): array
    {
        return [
            'id' => $id,
            'label' => $name,
            'email' => $email,
            'birth_date' => '1990-'.$this->date->format('m-d'),
            'lopd' => ['accepted' => true, 'no_commercial_info' => false],
        ];
    }

    /**
     * Gestión emitiendo bonos: `generarBonos` acepta el lote y
     * `consultarGeneracionBono` devuelve qué bono le tocó a cada cliente.
     *
     * @param  array<int, array<string, string>>  $bonos  idcliente => datos del bono
     */
    private function fakeErp(array $bonos): void
    {
        $lines = [];

        foreach ($bonos as $idCliente => $bono) {
            $lines[] = ['idcliente' => (string) $idCliente, 'bono' => $bono];
        }

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')->andReturn(['success' => true, 'batch_id' => '555']);
        $erp->shouldReceive('consultarGeneracionBono')->andReturn(['success' => true, 'lines' => $lines]);
        $erp->shouldReceive('consultaBono')->andReturn(['success' => false, 'message' => 'sin cupón global']);

        $this->app->instance(ErpService::class, $erp);
    }

    /**
     * Ajustes con tipo de bono y SIN código único: es el modo real, un bono por
     * cliente emitido en Gestión.
     */
    private function settings(array $overrides = []): BirthdaySettings
    {
        return new class($overrides) extends BirthdaySettings
        {
            public function __construct(private array $overrides = []) {}

            public function all(): array
            {
                return array_merge([
                    'window_start' => '09:00',
                    'window_end' => '14:00',
                    'throttle_per_hour' => 600,
                    'max_recipients' => 2000,
                    'leap_day_policy' => 'feb28',
                    'template_key' => 'birthday-coupon',
                    'audience_source' => 'api',
                    'bono_type_id' => 4,
                    'coupon_code' => '',
                    'coupon_verification_code' => '',
                    'coupon_valid_from' => '',
                    'coupon_valid_to' => '',
                    'coupon_amount' => '',
                    'coupon_min_purchase' => '',
                    'validate_against_erp' => true,
                    'commercial_optin' => true,
                    'lopd_accepted' => false,
                    'has_email' => true,
                    'check_suppressions' => true,
                ], $this->overrides);
            }
        };
    }
}
