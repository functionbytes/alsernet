<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpContextTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse::set() in ErpContextService passes an array but expects string — stub it out.
        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config(['helpdeskerp.manager_url' => 'http://manager.test']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdeskerp.view');
        $this->user->givePermissionTo('helpdeskerp.refresh');
        $this->user->givePermissionTo('helpdeskerp.health.view');
        $this->user->givePermissionTo('helpdeskerp.orders.detail.view');
    }

    /* ── Customer context ─────────────────────────────────────────────────── */

    public function test_authenticated_user_with_permission_can_get_customer_context(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [
                    ['id' => 1, 'label' => 'Juan', 'surnames' => 'García', 'email' => 'juan@ejemplo.com', 'cif' => '12345678Z'],
                ],
            ]),
            '*/erp/customer/1' => Http::response(['data' => ['phones' => [['number' => '600000000']], 'addresses' => [['city' => 'Madrid', 'province' => 'Madrid']], 'payment_method_id' => '30D']]),
            '*/erp/customer/1/balance' => Http::response(['data' => ['balance' => ['pending' => 100.0, 'invoiced' => 500.0], 'risk' => ['max_allowed' => 1000.0], 'loyalty_points' => 50]]),
            '*/erp/customer/1/orders*' => Http::response(['data' => [['id' => 99, 'number' => '2024/001', 'status' => 'SERV', 'date' => '2024-03-15', 'expected_date' => '2024-03-20', 'served_date' => null, 'warehouse' => 'CENTRAL', 'type' => 'P', 'observations' => null]], 'meta' => ['loading' => false]]),
            '*/erp/customer/1/invoices*' => Http::response(['data' => [['id' => 55, 'number' => 'F-2024-001', 'series' => 'F', 'year' => 2024, 'date' => '2024-03-20', 'status' => 'PAID', 'simplified' => false, 'payment_method' => 'Transferencia']]]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/juan@ejemplo.com/context');

        $response->assertOk()
            ->assertJsonPath('customer.found', true)
            ->assertJsonPath('customer.nif', '12345678Z')
            ->assertJsonPath('ordersLoading', false)
            ->assertJsonStructure([
                'customer' => ['found', 'id', 'name', 'email', 'nif', 'phone', 'city', 'province', 'credit_limit', 'balance', 'balance_invoiced', 'loyalty_points', 'payment_terms'],
                'orders' => [['id', 'number', 'status', 'date', 'expected_date', 'served_date', 'warehouse', 'type', 'observations']],
                'invoices' => [['id', 'number', 'series', 'year', 'date', 'status', 'simplified', 'payment_method']],
                'ordersLoading',
                'meta',
            ]);
    }

    public function test_user_without_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create();

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/juan@ejemplo.com/context')
            ->assertForbidden();
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/helpdeskErp/customers/juan@ejemplo.com/context')
            ->assertUnauthorized();
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/not-an-email/context')
            ->assertUnprocessable();
    }

    /* ── Refresh ──────────────────────────────────────────────────────────── */

    public function test_refresh_invalidates_cache(): void
    {
        $email = 'cached@example.com';
        $cacheKey = 'erp_ctx_'.md5(strtolower(trim($email)));

        // Pre-populate cache with a "found" customer
        Cache::put($cacheKey, [
            'customer' => ['found' => true, 'id' => 99, 'name' => 'Old Name'],
            'orders' => [],
            'invoices' => [],
            'orders_loading' => false,
            '_cached_at' => time(),
            '_ttl' => 600,
        ], 600);

        $this->assertTrue(Cache::has($cacheKey));

        // Manager now returns no customer for this email
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/helpdeskErp/customers/{$email}/context/refresh")
            ->assertOk()
            ->assertJsonPath('success', true);

        // The refresh must report the fresh (not-found) result, not the stale cached one
        $response->assertJsonPath('data.customer.found', false);
    }

    /* ── Health ───────────────────────────────────────────────────────────── */

    public function test_health_returns_status_ok_when_manager_reachable(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.oracle_reachable', true)
            ->assertJsonStructure(['success', 'data' => ['status', 'manager_url', 'oracle_reachable', 'latency_ms']]);
    }

    public function test_health_returns_degraded_when_manager_unreachable(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([], 503),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.oracle_reachable', false);
    }

    /* ── Orders loading flag ──────────────────────────────────────────────── */

    public function test_orders_loading_true_when_meta_loading_is_true(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => [['id' => 2, 'label' => 'Ana', 'surnames' => 'López', 'email' => 'ana@example.com', 'cif' => null]]]),
            '*/erp/customer/2' => Http::response(['data' => []]),
            '*/erp/customer/2/balance' => Http::response(['data' => []]),
            '*/erp/customer/2/orders*' => Http::response(['data' => [], 'meta' => ['loading' => true, 'retry_after' => 35]]),
            '*/erp/customer/2/invoices*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/ana@example.com/context')
            ->assertOk()
            ->assertJsonPath('ordersLoading', true);
    }
}
