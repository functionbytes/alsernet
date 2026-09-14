<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\Erp\Services\ErpService;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;
use Tests\TestCase;

/**
 * Preparación de la campaña del día: audiencia, exclusiones, guardas y reparto.
 *
 * El manager ERP se sirve con Http::fake — no hay Oracle desde aquí, y aunque
 * lo hubiera, un test no debe depender de quién cumple años de verdad.
 */
class PrepareBirthdayCampaignTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * No se usa SharesHelpdeskPdo: ese trait le presta a 'helpdesk' el PDO de
     * 'mariadb' y transacciona solo 'mariadb', con lo que Laravel no lleva la
     * cuenta del nivel de transacción de 'helpdesk' y la transacción explícita
     * del servicio revienta con "There is already an active transaction".
     *
     * Aquí no hace falta: las tablas del módulo viven en 'helpdesk' y
     * email_suppressions en la conexión por defecto, sin FK entre ellas, así que
     * cada una puede llevar su propia transacción sin locks cruzados.
     */
    protected array $connectionsToTransact = ['helpdesk', 'mysql', 'mariadb'];

    private CarbonImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija y lejana: campaign_date es UNIQUE y el entorno tiene
        // campañas reales en los días cercanos (el scheduler crea una al día).
        $this->date = CarbonImmutable::parse('2029-11-17');

        config()->set('helpdeskErp.manager_url', 'http://manager.test');
        config()->set('helpdeskbirthday.max_recipients', 2000);

        // La URL de la API de clientes se declara aquí y apunta al fake: si se
        // deja la de verdad (http://nginx), cada test consulta la API real y se
        // trae cientos de clientes con sus datos. Pasó: 573 destinatarios y 20 s
        // por test.
        config()->set('helpdeskbirthday.customers_api_url', 'http://manager.test');
        config()->set('helpdeskbirthday.audience_source', 'api');

        $this->app->instance(BirthdaySettings::class, $this->settings());
        $this->fakeErpCoupon();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_crea_la_campana_con_sus_destinatarios_y_su_reparto(): void
    {
        $this->fakeManager([
            $this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17'),
            $this->customer(2, 'luis@ejemplo.test', 'Luis', '1985-11-17'),
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(BirthdayCampaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertSame(2, $campaign->recipients_total);
        $this->assertSame(0, $campaign->skipped_count);

        $this->assertDatabaseHas('helpdesk_birthday_recipients', [
            'campaign_id' => $campaign->id,
            'email' => 'ana@ejemplo.test',
            'status' => BirthdayRecipient::STATUS_PENDING,
        ], 'helpdesk');

        // Con 2 destinatarios en una ventana de 5 h, el segundo sale mucho más
        // tarde que el primero: es justo el escalonado que se busca.
        $slots = $campaign->recipients()->orderBy('scheduled_at')->pluck('scheduled_at');
        $this->assertNotNull($slots[0]);
        $this->assertTrue($slots[1]->greaterThan($slots[0]));
    }

    public function test_los_suprimidos_quedan_omitidos_y_no_se_les_programa_envio(): void
    {
        EmailSuppression::create([
            'email' => 'luis@ejemplo.test',
            'module' => '',
            'reason' => 'unsubscribed',
        ]);

        $this->fakeManager([
            $this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17'),
            $this->customer(2, 'luis@ejemplo.test', 'Luis', '1985-11-17'),
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(1, $campaign->skipped_count);

        $luis = $campaign->recipients()->where('email', 'luis@ejemplo.test')->first();
        $this->assertSame(BirthdayRecipient::STATUS_SKIPPED, $luis->status);
        $this->assertSame(BirthdayRecipient::SKIP_SUPPRESSED, $luis->skip_reason);
        $this->assertNull($luis->scheduled_at);
    }

    public function test_los_emails_duplicados_solo_reciben_un_correo(): void
    {
        $this->fakeManager([
            $this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17'),
            $this->customer(2, 'ANA@ejemplo.test', 'Ana (ficha duplicada)', '1990-11-17'),
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(1, $campaign->recipients_total);
    }

    public function test_preparar_dos_veces_no_duplica_nada(): void
    {
        $this->fakeManager([$this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17')]);

        $service = app(BirthdayCampaignService::class);
        $first = $service->prepare($this->date);
        $second = $service->prepare($this->date);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, BirthdayRecipient::where('campaign_id', $first->id)->count());
    }

    public function test_la_guarda_aborta_si_el_erp_devuelve_de_mas(): void
    {
        config()->set('helpdeskbirthday.max_recipients', 3);

        $this->fakeManager(array_map(
            fn (int $i): array => $this->customer($i, "cliente{$i}@ejemplo.test", "Cliente {$i}", '1990-11-17'),
            range(1, 10),
        ));

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(BirthdayCampaign::STATUS_FAILED, $campaign->status);
        $this->assertStringContainsString('por encima del máximo', $campaign->error_message);
        $this->assertSame(0, $campaign->recipients()->count());
    }

    public function test_aborta_si_el_manager_ignora_el_filtro_de_cumpleanos(): void
    {
        // Un manager sin el filtro desplegado devuelve gente que no cumple hoy.
        $this->fakeManager([
            $this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17'),
            $this->customer(2, 'otro@ejemplo.test', 'Otro', '1974-03-15'),
        ]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        $this->assertSame(BirthdayCampaign::STATUS_FAILED, $campaign->status);
        $this->assertStringContainsString('no está desplegado en el manager', $campaign->error_message);
    }

    public function test_sin_tipo_de_bono_configurado_no_se_envia_nada(): void
    {
        $this->app->instance(BirthdaySettings::class, $this->settings(['bono_type_id' => 0]));
        $this->fakeManager([$this->customer(1, 'ana@ejemplo.test', 'Ana', '1990-11-17')]);

        $campaign = app(BirthdayCampaignService::class)->prepare($this->date);

        // Sin tipo de bono la campaña SÍ se prepara —quién cumple años hoy es un
        // dato que caduca— pero queda en pausa: reúne y programa, no envía.
        $this->assertSame(BirthdayCampaign::STATUS_PAUSED, $campaign->status);
        $this->assertStringContainsString('tipo de bono', $campaign->error_message);
        $this->assertFalse($campaign->isActive(), 'Una campaña en pausa no debe enviar.');
        $this->assertSame(1, $campaign->recipients()->count());
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

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

    private function customer(int $id, string $email, string $name, string $birthDate): array
    {
        return [
            'id' => $id,
            'label' => $name,
            'email' => $email,
            'birth_date' => $birthDate,
            'lopd' => ['accepted' => true, 'no_commercial_info' => false],
        ];
    }

    /**
     * Gestión emitiendo el bono de cada cliente: acepta el lote y luego dice
     * qué bono le tocó a quién.
     */
    private function fakeErpCoupon(): void
    {
        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')->andReturn(['success' => true, 'batch_id' => '777']);
        $erp->shouldReceive('consultarGeneracionBono')->andReturnUsing(
            fn (): array => [
                'success' => true,
                'lines' => [
                    ['idcliente' => '1', 'bono' => ['idbono_promocion' => '910001', 'codigo_verificacion' => 'AAA', 'importe' => '5.00']],
                    ['idcliente' => '2', 'bono' => ['idbono_promocion' => '910002', 'codigo_verificacion' => 'BBB', 'importe' => '5.00']],
                ],
            ]
        );

        $this->app->instance(ErpService::class, $erp);
    }

    /**
     * Ajustes deterministas: BirthdaySettings normal leería de la tabla
     * `settings`, y Setting::set() escapa de la transacción del test.
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
                    'commercial_optin' => true,
                    'lopd_accepted' => false,
                    'has_email' => true,
                    'check_suppressions' => true,
                ], $this->overrides);
            }
        };
    }
}
