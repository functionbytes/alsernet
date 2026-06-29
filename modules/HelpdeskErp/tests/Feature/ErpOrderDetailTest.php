<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for GET /api/helpdeskErp/customers/{customer}/orders/{order}
 *
 * Requires permission: helpdeskerp.orders.detail.view
 * Proxies to manager /api/erp/customer/{id}/orders/{orderId}.
 */
class ErpOrderDetailTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->user->givePermissionTo('helpdeskerp.orders.detail.view');
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/helpdeskErp/customers/1/orders/99')
            ->assertUnauthorized();
    }

    public function test_user_without_detail_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create();
        $noPermUser->givePermissionTo('helpdeskerp.view');

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/1/orders/99')
            ->assertForbidden();
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_manager_returns_order_data(): void
    {
        $detail = [
            'id' => 99,
            'number' => '2024/001',
            'status' => 'SERV',
            'lines' => [['ref' => 'PROD-001', 'qty' => 2, 'price' => 19.99]],
        ];

        Http::fake([
            '*/erp/customer/1/orders/99*' => Http::response(['data' => $detail]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/1/orders/99')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 99)
            ->assertJsonPath('data.number', '2024/001')
            ->assertJsonStructure(['success', 'data']);
    }

    // ── Error / not found ──────────────────────────────────────────────────

    public function test_manager_returns_404_gives_not_found_response(): void
    {
        Http::fake([
            '*/erp/customer/1/orders/404*' => Http::response(['error' => 'not found'], 404),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/1/orders/404')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_manager_server_error_returns_not_found(): void
    {
        Http::fake([
            '*/erp/customer/1/orders/99*' => Http::response([], 500),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/1/orders/99')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
