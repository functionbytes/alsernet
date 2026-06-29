<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\HelpdeskTickets\Services\HelpdeskTicketBridgeService;
use Tests\TestCase;

/**
 * Feature coverage for the HelpdeskTickets bridge service.
 *
 * The service is the concrete implementation of TicketServiceContract that
 * Helpdesk uses through DI. These tests verify the contract is wired up,
 * the bridge knows the routes it needs to generate URLs, and the public
 * API surface returns the documented shapes.
 *
 * Tests are intentionally smoke-style (shape + non-throwing) instead of
 * asserting concrete data so they don't depend on test fixtures of the
 * helpdesk schema (which lives in a separate connection).
 */
class HelpdeskTicketBridgeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private HelpdeskTicketBridgeService $bridge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bridge = $this->app->make(HelpdeskTicketBridgeService::class);
    }

    public function test_implements_helpdesk_contract(): void
    {
        $this->assertInstanceOf(TicketServiceContract::class, $this->bridge);
    }

    public function test_is_available_returns_true(): void
    {
        $this->assertTrue($this->bridge->isAvailable());
    }

    public function test_show_route_required_for_url_generation_exists(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.tickets.show'));
    }

    public function test_get_ticket_detail_returns_null_when_not_found(): void
    {
        $this->skipUnlessHelpdeskDbAvailable();

        $this->assertNull($this->bridge->getTicketDetail(0));
    }

    public function test_get_categories_returns_collection(): void
    {
        $this->skipUnlessHelpdeskDbAvailable();

        $categories = $this->bridge->getCategories();

        $this->assertInstanceOf(Collection::class, $categories);

        if ($categories->isNotEmpty()) {
            $first = $categories->first();
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('name', $first);
        }
    }

    public function test_get_dashboard_data_returns_documented_shape(): void
    {
        $this->skipUnlessHelpdeskDbAvailable();

        $data = $this->bridge->getDashboardData();

        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('topAgents', $data);
        $this->assertArrayHasKey('recentBreaches', $data);
        $this->assertArrayHasKey('recentTickets', $data);
        $this->assertArrayHasKey('avgRating', $data);

        foreach (['open', 'closed_today', 'created_today', 'sla_breached', 'unassigned', 'overdue'] as $key) {
            $this->assertArrayHasKey($key, $data['stats']);
            $this->assertIsInt($data['stats'][$key]);
        }

        $this->assertInstanceOf(Collection::class, $data['topAgents']);
        $this->assertInstanceOf(Collection::class, $data['recentBreaches']);
        $this->assertInstanceOf(Collection::class, $data['recentTickets']);
        $this->assertTrue(is_float($data['avgRating']) || is_int($data['avgRating']));
    }

    public function test_get_agent_dashboard_data_returns_documented_shape(): void
    {
        $this->skipUnlessHelpdeskDbAvailable();

        $data = $this->bridge->getAgentDashboardData(0);

        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recentTickets', $data);

        foreach (['open', 'closed_this_month', 'sla_breached', 'avg_rating'] as $key) {
            $this->assertArrayHasKey($key, $data['stats']);
        }

        $this->assertInstanceOf(Collection::class, $data['recentTickets']);
    }

    public function test_bridge_is_resolved_via_helpdesk_contract_binding(): void
    {
        $resolved = $this->app->make(TicketServiceContract::class);

        $this->assertInstanceOf(HelpdeskTicketBridgeService::class, $resolved);
    }

    /**
     * Skip the current test when the helpdesk schema is not present in the
     * testing connection. The bridge service issues raw queries against
     * `helpdesk_tickets`/`helpdesk_ticket_categories`, so without the schema
     * those tests would assert failure on infrastructure rather than logic.
     */
    private function skipUnlessHelpdeskDbAvailable(): void
    {
        try {
            DB::connection('helpdesk')
                ->select('SELECT 1 FROM helpdesk_tickets LIMIT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Helpdesk schema not available in test DB: '.$e->getMessage());
        }

        // Some bridge methods join users with SoftDeletes — skip if the test
        // mariadb connection doesn't include the deleted_at column.
        try {
            DB::connection('mariadb')
                ->select('SELECT deleted_at FROM users LIMIT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('users.deleted_at column missing in test DB: '.$e->getMessage());
        }
    }
}
