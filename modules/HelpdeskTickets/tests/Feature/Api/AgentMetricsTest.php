<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Métricas de calidad por agente en /api/v1/helpdesk/metrics/by-agent:
 * CSAT medio, cumplimiento de SLA (%) y tiempo medio de resolución del mes.
 */
class AgentMetricsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.metrics.view', 'guard_name' => 'web']);

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.metrics.view');

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::firstOrCreate(
            ['email' => 'agent-metrics@example.com'],
            ['name' => 'Metrics Customer']
        );
    }

    public function test_by_agent_returns_quality_metrics_for_the_current_month(): void
    {
        // Cerrado este mes: rating 5, dentro de SLA, resuelto en 60 min.
        $good = $this->ticket([
            'assignee_id' => $this->agent->id,
            'rating' => 5,
            'rated_at' => now(),
            'sla_resolution_breached' => false,
            'closed_at' => now(),
        ]);
        $good->forceFill(['created_at' => now()->subMinutes(60)])->saveQuietly();

        // Cerrado este mes pero fuera de SLA (para que el compliance sea 50%).
        $breached = $this->ticket([
            'assignee_id' => $this->agent->id,
            'sla_resolution_breached' => true,
            'closed_at' => now(),
        ]);
        $breached->forceFill(['created_at' => now()->subMinutes(180)])->saveQuietly();

        $response = $this->actingAs($this->agent)
            ->getJson('/api/v1/helpdesk/metrics/by-agent')
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('agentId', $this->agent->id);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['resolvedThisMonth']);
        $this->assertEquals(5, $row['avgRating']);
        $this->assertEquals(50, $row['slaComplianceRate']);   // 1 de 2 dentro de SLA
        $this->assertSame(120, $row['avgResolutionMinutes']); // (60 + 180) / 2
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Metrics ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
