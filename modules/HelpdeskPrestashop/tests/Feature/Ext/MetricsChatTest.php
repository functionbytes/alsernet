<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskPrestashop\Services\Ext\MetricsChatService;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Métricas del chat · PrestaShop»: los KPIs, la serie semanal, el ranking
 * y el CSV salen solo de filas del log 'helpdeskprestashop' con las
 * properties que guarda cada acción real. La pantalla y el CSV exigen
 * helpdeskprestashop.metrics.view.
 */
class MetricsChatTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        Permission::firstOrCreate(['name' => 'helpdeskprestashop.metrics.view', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.metrics.view');

        return $user;
    }

    private function log(User $causer, string $description, array $properties, ?string $log = 'helpdeskprestashop', ?\DateTimeInterface $at = null): Activity
    {
        $activity = activity($log)->causedBy($causer)->withProperties($properties)->log($description);

        if ($at !== null) {
            $activity->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        }

        return $activity;
    }

    /**
     * Las mismas properties que guardan los controladores/listener reales.
     */
    private function seedActions(User $agent): void
    {
        $this->log($agent, 'ps.voucher.created', ['code' => 'COMP-1', 'amount' => 10.5, 'validity_days' => 30]);
        $this->log($agent, 'ps.voucher.created', ['code' => 'COMP-2', 'amount' => 4.5, 'validity_days' => 30]);
        $this->log($agent, 'ps.voucher.duplicated', ['code' => 'DUP-1', 'amount' => null, 'percent' => 10]);
        $this->log($agent, 'ps.refund.issued', ['order_id' => 900001, 'amount' => 33.1]);
        $this->log($agent, 'ps.orders.status', ['route' => 'manager.helpdesk.ps.orders.status', 'params' => ['order' => 900002], 'input' => ['state_id' => 6, 'state_name' => 'Cancelado']]);
        $this->log($agent, 'ps.orders.status', ['params' => ['order' => 900003], 'input' => ['state_id' => 2, 'state_name' => 'Pago aceptado']]);
        $this->log($agent, 'ps.rma.state_changed', ['return_id' => 11, 'state_id' => 5, 'state_name' => 'Devolución completada']);
        $this->log($agent, 'ps.rma.state_changed', ['return_id' => 12, 'state_id' => 4, 'state_name' => 'Devolución denegada']);
        $this->log($agent, 'ps.rma.state_changed', ['return_id' => 13, 'state_id' => 2, 'state_name' => 'A la espera del paquete']);
        $this->log($agent, 'ps.cart.converted', ['cart_id' => 77, 'order_id' => 900004, 'total' => 99.9]);
        $this->log($agent, 'ps.cart.emptied', ['cart_id' => 78, 'removed_products' => 2]);
        $this->log($agent, 'ps.orders.address', ['params' => ['order' => 900005], 'input' => ['type' => 'delivery']]);
        $this->log($agent, 'ps.cart.address', ['params' => ['cart' => 79]]);
        $this->log($agent, 'ps.address.updated', ['address_id' => 5]);
        $this->log($agent, 'ps.catalog.stock_alert', ['product_id' => 321]);
        $this->log($agent, 'ps.orders.note', ['params' => ['order' => 900006], 'fields' => ['note']]);
    }

    public function test_screen_and_export_require_metrics_view(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('manager.helpdesk.ps.ext.metrics.index'))->assertForbidden();
        $this->actingAs($user)->get(route('manager.helpdesk.ps.ext.metrics.export'))->assertForbidden();
    }

    public function test_summary_counts_and_amounts_come_from_activity_properties(): void
    {
        $agent = User::factory()->create();
        $this->seedActions($agent);

        // Fuera de las métricas: otro log, operación de opslog y fuera del periodo.
        $this->log($agent, 'ps.voucher.created', ['amount' => 500], 'helpdeskprestashop-config');
        $this->log($agent, 'ps.ops.webhooks_requeued', ['count' => 3]);
        $this->log($agent, 'ps.refund.issued', ['amount' => 700], 'helpdeskprestashop', now()->subDays(40));

        $summary = app(MetricsChatService::class)->summary(30, $agent->id);
        $kpis = $summary['kpis'];

        $this->assertSame(3, $kpis['vouchers']['count']);
        $this->assertEqualsWithDelta(15.0, $kpis['vouchers']['amount'], 0.001);
        $this->assertSame(2, $kpis['vouchers']['with_amount']);
        $this->assertSame(1, $summary['details']['vouchers_percent']);

        $this->assertSame(1, $kpis['refunds']['count']);
        $this->assertEqualsWithDelta(33.1, $kpis['refunds']['amount'], 0.001);

        $this->assertSame(1, $kpis['cancelled']['count']);
        $this->assertSame(2, $kpis['returns']['count']);
        $this->assertSame(1, $summary['details']['returns_completed']);
        $this->assertSame(1, $summary['details']['returns_denied']);

        $this->assertSame(1, $kpis['carts_converted']['count']);
        $this->assertEqualsWithDelta(99.9, $kpis['carts_converted']['amount'], 0.001);
        $this->assertSame(1, $kpis['carts_emptied']['count']);
        $this->assertSame(3, $kpis['addresses']['count']);
        $this->assertSame(1, $kpis['stock_alerts']['count']);

        // Pago aceptado, RMA a la espera y la nota cuentan como «otras».
        $this->assertSame(3, $summary['other']);
        $this->assertSame(16, $summary['total']);

        $this->assertCount(1, $summary['ranking']);
        $this->assertSame($agent->id, $summary['ranking'][0]['id']);
        $this->assertSame(16, $summary['ranking'][0]['total']);

        $week = collect($summary['series']['all']['bars'])->firstWhere('n', '>', 0);
        $this->assertSame(16, $week['n']);
        $this->assertSame('psc-h-100', $week['height']);
    }

    public function test_agent_filter_and_ranking_order(): void
    {
        $busy = User::factory()->create(['firstname' => 'Agente', 'lastname' => 'Ocupado']);
        $quiet = User::factory()->create(['firstname' => 'Agente', 'lastname' => 'Tranquilo']);
        $this->seedActions($busy);
        $this->log($quiet, 'ps.catalog.stock_alert', ['product_id' => 1]);

        $service = app(MetricsChatService::class);

        $ranking = collect($service->summary(7)['ranking'])->whereIn('id', [$busy->id, $quiet->id])->values();
        $this->assertSame([$busy->id, $quiet->id], $ranking->pluck('id')->all());

        $onlyQuiet = $service->summary(7, $quiet->id);
        $this->assertSame(1, $onlyQuiet['total']);
        $this->assertSame(1, $onlyQuiet['kpis']['stock_alerts']['count']);
        $this->assertSame(0, $onlyQuiet['kpis']['vouchers']['count']);
    }

    public function test_screen_renders_kpis_and_money_format(): void
    {
        $viewer = $this->viewer();
        $this->seedActions($viewer);

        $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.metrics.index', ['days' => 7, 'agent' => $viewer->id]))
            ->assertOk()
            ->assertSee('Métricas del chat · PrestaShop', false)
            ->assertSee('Vales emitidos', false)
            ->assertSee('15,00 €', false)
            ->assertSee('33,10 €', false)
            ->assertSee('99,90 € en pedidos', false)
            ->assertSee('1 de porcentaje, sin importe', false)
            ->assertSee('1 completadas · 1 denegadas', false)
            ->assertSee('psc-bars', false);
    }

    public function test_invalid_period_falls_back_to_default(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('manager.helpdesk.ps.ext.metrics.index', ['days' => 365]))
            ->assertOk()
            ->assertViewHas('days', 30);
    }

    public function test_csv_exports_agents_summary_and_detail(): void
    {
        $viewer = $this->viewer();
        $this->seedActions($viewer);

        $agents = $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.metrics.export', ['days' => 7, 'agent' => $viewer->id]))
            ->assertOk();
        $this->assertStringContainsString('text/csv', $agents->headers->get('Content-Type'));
        $csv = $agents->streamedContent();
        $this->assertStringContainsString('Vales emitidos · importe (€)', $csv);
        $this->assertStringContainsString('15,00', $csv);
        $this->assertStringContainsString('Total;', $csv);

        $detail = $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.metrics.export', ['days' => 7, 'agent' => $viewer->id, 'kind' => 'detail']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('ps.refund.issued', $detail);
        $this->assertStringContainsString('33,10', $detail);
        $this->assertStringContainsString('COMP-1', $detail);
        // La nota del pedido no es ninguna métrica: no sale en el detalle.
        $this->assertStringNotContainsString('ps.orders.note', $detail);
    }
}
