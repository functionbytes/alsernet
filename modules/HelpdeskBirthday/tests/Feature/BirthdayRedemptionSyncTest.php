<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Models\BirthdayRedemption;
use Modules\HelpdeskBirthday\Services\BirthdayRedemptionSyncService;
use Modules\HelpdeskBirthday\Services\Redemption\BirthdayRedemptionReader;
use Tests\TestCase;

/**
 * La copia local de canjes: que se llene bien, que no duplique y que ate cada
 * canje a quien le corresponde.
 *
 * Las tres cosas que se comprueban aquí salieron de mirar la tienda real:
 *
 *  - PrestaShop borra la `cart_rule` al consumirla, así que un canje puede
 *    llegar sin código (363 de los 1.116 históricos) y hay que guardarlo igual.
 *  - Un pedido puede llevar dos bonos: la identidad es la línea de descuento,
 *    no el pedido. Con la clave puesta en el pedido, 11 canjes se machacaban.
 *  - Quien compró con un código reenviado canjeó el bono pero NO es conversión
 *    de la campaña: esa diferencia es la que se mira para decidir si repetirla.
 */
class BirthdayRedemptionSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk', 'mysql', 'mariadb'];

    private CarbonImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha lejana: campaign_date es UNIQUE y el scheduler crea una real
        // cada día.
        $this->date = CarbonImmutable::parse('2029-11-21');
    }

    public function test_guarda_los_canjes_y_los_ata_a_su_destinatario(): void
    {
        $campaign = $this->campaign();
        $ana = $this->recipient($campaign, 'ana@ejemplo.test', '910001', 'AAA', BirthdayRecipient::STATUS_SENT);

        $this->fakeReader([
            $this->row(line: 1, order: 5001, code: '910001-AAA', email: 'ana@ejemplo.test', total: 120.0),
        ])->sync();

        $r = BirthdayRedemption::where('ps_order_line_id', 1)->first();

        $this->assertNotNull($r);
        $this->assertSame($ana->id, $r->recipient_id);
        $this->assertSame($campaign->id, $r->campaign_id);
        // Le mandamos el correo y compró: eso es conversión de la campaña.
        $this->assertTrue($r->attributed);
        $this->assertSame('120.00', (string) $r->order_total);
    }

    public function test_un_canje_sin_codigo_se_guarda_y_se_ata_por_email(): void
    {
        $campaign = $this->campaign();
        $luis = $this->recipient($campaign, 'luis@ejemplo.test', '910002', 'BBB', BirthdayRecipient::STATUS_SENT);

        // La cart_rule ya no existe y gestión nunca lo registró: es el caso de
        // 363 de los canjes reales de la tienda.
        $this->fakeReader([
            $this->row(line: 2, order: 5002, code: null, email: 'luis@ejemplo.test', total: 80.0),
        ])->sync();

        $r = BirthdayRedemption::where('ps_order_line_id', 2)->first();

        $this->assertNotNull($r, 'Un canje sin código sigue siendo un canje real.');
        $this->assertNull($r->coupon_code);
        $this->assertSame($luis->id, $r->recipient_id, 'Sin código, el email es lo único que queda.');
        $this->assertTrue($r->attributed);
    }

    public function test_no_atribuye_a_quien_no_recibio_el_correo(): void
    {
        $campaign = $this->campaign();
        // Se quedó sin bono, así que nunca recibió nada que canjear.
        $this->recipient($campaign, 'sin@ejemplo.test', '910003', 'CCC', BirthdayRecipient::STATUS_SKIPPED);

        $this->fakeReader([
            $this->row(line: 3, order: 5003, code: '910003-CCC', email: 'sin@ejemplo.test', total: 50.0),
        ])->sync();

        $r = BirthdayRedemption::where('ps_order_line_id', 3)->first();

        $this->assertNotNull($r);
        $this->assertFalse($r->attributed, 'El canje existe, pero no es conversión de la campaña.');
    }

    public function test_repetir_la_sincronizacion_no_duplica_nada(): void
    {
        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test', '910001', 'AAA', BirthdayRecipient::STATUS_SENT);

        $rows = [$this->row(line: 4, order: 5004, code: '910001-AAA', email: 'ana@ejemplo.test', total: 99.0)];

        $this->fakeReader($rows)->sync();
        $this->fakeReader($rows)->sync();

        $this->assertSame(1, BirthdayRedemption::where('ps_order_line_id', 4)->count());
    }

    public function test_dos_bonos_en_el_mismo_pedido_son_dos_canjes(): void
    {
        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test', '910001', 'AAA', BirthdayRecipient::STATUS_SENT);

        // Mismo pedido, dos líneas de descuento. Con la clave puesta en el
        // pedido, la segunda pisaba a la primera.
        $this->fakeReader([
            $this->row(line: 10, order: 5010, code: '910001-AAA', email: 'ana@ejemplo.test', total: 200.0),
            $this->row(line: 11, order: 5010, code: null, email: 'ana@ejemplo.test', total: 200.0),
        ])->sync();

        $this->assertSame(2, BirthdayRedemption::where('ps_order_id', 5010)->count());
    }

    public function test_marca_el_descuadre_cuando_gestion_no_lo_registro(): void
    {
        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test', '910001', 'AAA', BirthdayRecipient::STATUS_SENT);

        $this->fakeReader([
            $this->row(line: 20, order: 5020, code: '910001-AAA', email: 'ana@ejemplo.test', total: 60.0, erpMarked: false),
            $this->row(line: 21, order: 5021, code: '910001-AAA', email: 'ana@ejemplo.test', total: 70.0, erpMarked: true),
        ])->sync();

        $sinCuadrar = BirthdayRedemption::query()
            ->where('campaign_id', $campaign->id)
            ->unreconciled()
            ->pluck('ps_order_line_id');

        $this->assertEqualsCanonicalizing([20], $sinCuadrar->all());
    }

    // -----------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function fakeReader(array $rows): BirthdayRedemptionSyncService
    {
        $reader = new class($rows) implements BirthdayRedemptionReader
        {
            /** @param array<int, array<string, mixed>> $rows */
            public function __construct(private array $rows) {}

            public function isAvailable(): bool
            {
                return true;
            }

            public function label(): string
            {
                return 'test';
            }

            public function redemptions(?string $from, ?string $to, ?string $nameLike = null, int $limit = 500, int $offset = 0): array
            {
                return $offset === 0 ? $this->rows : [];
            }

            public function count(?string $from, ?string $to, ?string $nameLike = null): int
            {
                return count($this->rows);
            }
        };

        return new BirthdayRedemptionSyncService($reader);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        int $line,
        int $order,
        ?string $code,
        string $email,
        float $total,
        bool $erpMarked = false,
    ): array {
        return [
            'line_id' => $line,
            'code' => $code,
            'code_source' => $code !== null ? 'erp' : null,
            'voucher_name' => 'Cheque cumpleaños generado desde la web',
            'cart_rule_id' => 0,
            'order_id' => $order,
            'order_reference' => 'REF'.$order,
            'order_total' => $total,
            'order_date' => $this->date->addDays(2)->toDateTimeString(),
            'order_valid' => true,
            'order_state' => 'Pago aceptado',
            'customer_id' => 900,
            'customer_email' => $email,
            'discount' => 5.0,
            'erp' => [
                'marked' => $erpMarked,
                'bono' => $code !== null ? explode('-', $code)[0] : null,
                'operation' => $erpMarked ? 2 : null,
                'response' => $erpMarked ? 'OK' : null,
                'sale_amount' => $erpMarked ? $total : null,
                'marked_at' => $erpMarked ? $this->date->toDateTimeString() : null,
            ],
        ];
    }

    private function campaign(): BirthdayCampaign
    {
        BirthdayCampaign::query()->whereDate('campaign_date', $this->date->toDateString())->delete();

        return BirthdayCampaign::query()->create([
            'campaign_date' => $this->date->toDateString(),
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'template_key' => 'birthday-coupon',
            'window_start' => '09:00:00',
            'window_end' => '14:00:00',
            'throttle_per_hour' => 600,
            'interval_seconds' => 60,
            'recipients_total' => 1,
            'sent_count' => 1,
        ]);
    }

    private function recipient(
        BirthdayCampaign $campaign,
        string $email,
        string $code,
        string $verification,
        string $status,
    ): BirthdayRecipient {
        return $campaign->recipients()->create([
            'email' => $email,
            'name' => 'Cliente',
            'erp_customer_id' => '1',
            'coupon_code' => $code,
            'coupon_verification_code' => $verification,
            'scheduled_at' => $this->date->setTime(9, 0),
            'status' => $status,
        ]);
    }
}
