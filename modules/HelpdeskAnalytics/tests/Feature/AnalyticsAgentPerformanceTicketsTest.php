<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskAnalytics\Services\AnalyticsAggregatorService;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * agentPerformance() cruza conversaciones (bandeja) y tickets (sin bandeja) en
 * un único ranking por agente — antes eran comparativas separadas y un agente
 * con solo tickets cerrados (sin conversaciones) no aparecía en absoluto.
 */
class AnalyticsAgentPerformanceTicketsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Setting::set('tickets.integration_enabled', '1', 'tickets');
    }

    public function test_agent_with_only_closed_tickets_appears_in_the_ranking(): void
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $agent = User::factory()->create(['firstname' => 'Solo', 'lastname' => 'Tickets']);

        Ticket::factory()->count(2)->create([
            'assignee_id' => $agent->id,
            'closed_at' => now(),
            'first_response_at' => now()->addMinutes(20),
        ]);

        $rows = collect(app(AnalyticsAggregatorService::class)->agentPerformance($from, $to));
        $row = $rows->firstWhere('name', 'Solo Tickets');

        $this->assertNotNull($row, 'Un agente con solo tickets cerrados debe aparecer en el ranking.');
        $this->assertSame(2, $row['ticket_closed_count']);
        $this->assertSame(0, $row['closed_count'], 'No tiene conversaciones cerradas.');
        $this->assertSame(2, $row['total_closed']);
        $this->assertGreaterThan(0, $row['ticket_avg_first_response_minutes']);
        $this->assertGreaterThanOrEqual(0, $row['ticket_avg_resolution_minutes']);
    }

    public function test_ticket_side_of_the_ranking_is_zeroed_when_tickets_integration_disabled(): void
    {
        Setting::set('tickets.integration_enabled', '0', 'tickets');

        $agent = User::factory()->create(['firstname' => 'Disabled', 'lastname' => 'Agent']);

        Ticket::factory()->create([
            'assignee_id' => $agent->id,
            'closed_at' => now(),
        ]);

        $rows = collect(app(AnalyticsAggregatorService::class)
            ->agentPerformance(now()->startOfMonth(), now()->endOfMonth()));

        // Con la integración de tickets apagada, el agente no tiene actividad
        // de conversaciones ni de tickets: no debe aparecer en absoluto.
        $this->assertNull($rows->firstWhere('name', 'Disabled Agent'));
    }
}
