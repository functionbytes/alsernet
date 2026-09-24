<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Models\ErpAdminMetricEvent;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminMetricsService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Métricas de Gestión»: registro tras la respuesta de las rutas del panel,
 * pantalla, export CSV, permisos y purga.
 */
class ErpAdminMetricsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const URL = '/panel/helpdesk/erp/metrics';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.ext.admin.metrics.enabled' => true,
        ]);
        Cache::flush();
        Http::preventStrayRequests();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        if (! app(ErpAdminMetricsService::class)->ready()) {
            $this->markTestSkipped('Falta la migración de helpdesk_erp_metrics_events.');
        }
    }

    public function test_panel_request_is_recorded_after_the_response(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdeskerp.view', 'helpdesk.customers.manage']);

        $this->actingAs($agent)
            ->getJson('/panel/helpdesk/customers/'.$customer->id.'/erp/overview')
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_erp_metrics_events', [
            'kind' => 'overview',
            'user_id' => $agent->id,
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_nothing_is_recorded_when_disabled(): void
    {
        config(['helpdeskErp.ext.admin.metrics.enabled' => false]);
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdeskerp.view', 'helpdesk.customers.manage']);

        $this->actingAs($agent)->getJson('/panel/helpdesk/customers/'.$customer->id.'/erp/overview');

        $this->assertDatabaseMissing('helpdesk_erp_metrics_events', ['customer_id' => $customer->id], 'helpdesk');
    }

    public function test_screen_and_export_require_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(self::URL)->assertForbidden();
        $this->actingAs($user)->get(self::URL.'/export')->assertForbidden();
    }

    public function test_supervisor_sees_metrics_and_exports_csv(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskerp.metrics.view');

        ErpAdminMetricEvent::query()->insert([
            ['occurred_at' => now()->format('Y-m-d H:i:s'), 'user_id' => $user->id, 'customer_id' => 1, 'kind' => 'overview', 'section' => null, 'state' => 'unlinked', 'duration_ms' => 40, 'blocked_count' => 2],
            ['occurred_at' => now()->format('Y-m-d H:i:s'), 'user_id' => $user->id, 'customer_id' => 1, 'kind' => 'section', 'section' => 'balance', 'state' => 'blocked', 'duration_ms' => 60, 'blocked_count' => 1],
            ['occurred_at' => now()->format('Y-m-d H:i:s'), 'user_id' => $user->id, 'customer_id' => 1, 'kind' => 'manager_call', 'section' => 'summary', 'state' => 'ok', 'duration_ms' => 900, 'blocked_count' => 0],
        ]);

        $this->actingAs($user)
            ->get(self::URL.'?days=7&agent='.$user->id)
            ->assertOk()
            ->assertSee('Resúmenes abiertos')
            ->assertSee('Saldo');

        // Sin estilos en línea en las vistas del módulo (el layout del tema
        // sí trae alguno, así que no se mira la página entera).
        $this->assertAdminViewsHaveNoInlineStyles();

        $kpis = app(ErpAdminMetricsService::class)->kpis(7, $user->id);
        $this->assertSame(1, $kpis['overview']);
        $this->assertSame(1, $kpis['unlinked_customers']);
        $this->assertSame(3, $kpis['blocked']);
        $this->assertSame(900, $kpis['manager_p50']);

        $csv = $this->actingAs($user)->get(self::URL.'/export?days=7&agent='.$user->id)->assertOk()->streamedContent();
        $this->assertStringContainsString('Resumen abierto', $csv);
        $this->assertStringContainsString('balance', $csv);
    }

    public function test_purge_deletes_events_older_than_retention(): void
    {
        ErpAdminMetricEvent::query()->insert([
            ['occurred_at' => now()->subDays(200)->format('Y-m-d H:i:s'), 'user_id' => null, 'customer_id' => 987654321, 'kind' => 'overview', 'section' => null, 'state' => 'ok', 'duration_ms' => 1, 'blocked_count' => 0],
            ['occurred_at' => now()->format('Y-m-d H:i:s'), 'user_id' => null, 'customer_id' => 987654322, 'kind' => 'overview', 'section' => null, 'state' => 'ok', 'duration_ms' => 1, 'blocked_count' => 0],
        ]);

        $this->artisan('helpdeskerp:purge-metrics', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_erp_metrics_events', ['customer_id' => 987654321], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_erp_metrics_events', ['customer_id' => 987654322], 'helpdesk');
    }

    public function test_percentile_is_nearest_rank(): void
    {
        $this->assertNull(ErpAdminMetricsService::percentile([], 50));
        $this->assertSame(3, ErpAdminMetricsService::percentile([1, 2, 3, 4, 5], 50));
        $this->assertSame(100, ErpAdminMetricsService::percentile(range(1, 100), 100));
        $this->assertSame(95, ErpAdminMetricsService::percentile(range(1, 100), 95));
    }

    private function assertAdminViewsHaveNoInlineStyles(): void
    {
        $files = glob(module_path('HelpdeskErp', 'resources/views/admin/*.blade.php')) ?: [];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('style="', (string) file_get_contents($file), basename($file).' tiene estilos en línea.');
        }
    }
}
