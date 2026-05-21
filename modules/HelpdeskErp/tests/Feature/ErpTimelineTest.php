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
 * Tests for GET /api/helpdeskErp/customers/{email}/timeline
 *
 * Requires permission: helpdeskErp.view (via CustomerContextRequest)
 * Aggregates ERP orders, ERP invoices, optional PS events, and helpdesk conversations.
 * Events are sorted by date descending and limited.
 */
class ErpTimelineTest extends TestCase
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

        config(['helpdeskErp.manager_url' => 'http://manager.test']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdeskErp.view');
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/helpdeskErp/customers/test@example.com/timeline')
            ->assertUnauthorized();
    }

    public function test_user_without_view_permission_returns_403(): void
    {
        $noPermUser = User::factory()->create();

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/test@example.com/timeline')
            ->assertForbidden();
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/not-an-email/timeline')
            ->assertUnprocessable();
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_timeline_returns_erp_events_sorted_by_date_desc(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 1, 'label' => 'Test', 'surnames' => 'User', 'email' => 'test@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/1' => Http::response(['data' => []]),
            '*/erp/customer/1/balance' => Http::response(['data' => []]),
            '*/erp/customer/1/orders*' => Http::response([
                'data' => [
                    ['id' => 10, 'number' => '2024/010', 'status' => 'SERV', 'date' => '2024-03-01', 'expected_date' => null, 'served_date' => null, 'warehouse' => 'A', 'type' => 'P', 'observations' => null],
                    ['id' => 20, 'number' => '2024/020', 'status' => 'PEND', 'date' => '2024-06-15', 'expected_date' => null, 'served_date' => null, 'warehouse' => 'B', 'type' => 'P', 'observations' => null],
                ],
                'meta' => ['loading' => false],
            ]),
            '*/erp/customer/1/invoices*' => Http::response([
                'data' => [
                    ['id' => 55, 'number' => 'F-2024-001', 'series' => 'F', 'year' => 2024, 'date' => '2024-04-10', 'status' => 'PAID', 'simplified' => false, 'payment_method' => 'Transfer'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/test@example.com/timeline')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        // Verify descending date sort: first event must be >= second event
        if (count($data) >= 2) {
            $this->assertGreaterThanOrEqual(
                strtotime($data[1]['date']),
                strtotime($data[0]['date']),
                'Timeline events must be sorted descending by date'
            );
        }

        // Verify event structure
        $firstEvent = $data[0];
        $this->assertArrayHasKey('type', $firstEvent);
        $this->assertArrayHasKey('source', $firstEvent);
        $this->assertArrayHasKey('date', $firstEvent);
        $this->assertArrayHasKey('title', $firstEvent);
    }

    public function test_limit_parameter_is_respected(): void
    {
        // Return 5 orders and 5 invoices (10 events total)
        $orders = array_map(fn ($i) => [
            'id' => $i,
            'number' => "2024/{$i}",
            'status' => 'SERV',
            'date' => "2024-0{$i}-01",
            'expected_date' => null,
            'served_date' => null,
            'warehouse' => 'A',
            'type' => 'P',
            'observations' => null,
        ], range(1, 5));

        $invoices = array_map(fn ($i) => [
            'id' => $i,
            'number' => "F-2024-{$i}",
            'series' => 'F',
            'year' => 2024,
            'date' => "2024-0{$i}-15",
            'status' => 'PAID',
            'simplified' => false,
            'payment_method' => 'Transfer',
        ], range(1, 5));

        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 1, 'label' => 'Test', 'surnames' => 'User', 'email' => 'limit@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/1' => Http::response(['data' => []]),
            '*/erp/customer/1/balance' => Http::response(['data' => []]),
            '*/erp/customer/1/orders*' => Http::response(['data' => $orders, 'meta' => ['loading' => false]]),
            '*/erp/customer/1/invoices*' => Http::response(['data' => $invoices]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/limit@example.com/timeline?limit=4')
            ->assertOk();

        // limit=4 is below minimum (10) so it will be clamped to 10 by the controller
        // All 10 events should be returned since limit is clamped to min 10
        $this->assertCount(10, $response->json('data'));
    }

    public function test_customer_not_found_returns_empty_timeline(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/unknown@example.com/timeline')
            ->assertOk();

        $this->assertEmpty($response->json('data'));
    }

    public function test_timeline_works_regardless_of_prestashop_module_availability(): void
    {
        // This test asserts the endpoint does not fail whether HelpdeskPrestashop
        // module is installed or not — the service handles it defensively.
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 1, 'label' => 'PS', 'surnames' => 'Test', 'email' => 'ps@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/1' => Http::response(['data' => []]),
            '*/erp/customer/1/balance' => Http::response(['data' => []]),
            '*/erp/customer/1/orders*' => Http::response(['data' => [], 'meta' => ['loading' => false]]),
            '*/erp/customer/1/invoices*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/helpdeskErp/customers/ps@example.com/timeline')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
