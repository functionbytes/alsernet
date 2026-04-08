<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketStatus;
use Modules\Helpdesk\Services\AssignmentService;
use Tests\TestCase;

class AutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private TicketStatus $openStatus;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );

        $this->customer = Customer::create([
            'name' => 'Auto-assign Customer',
            'email' => 'autoassign@example.com',
        ]);
    }

    public function test_auto_assign_by_workload_picks_agent_with_fewest_tickets(): void
    {
        // Create two agents
        $agentA = $this->createAgent('Agent A', 'agentA@example.com');
        $agentB = $this->createAgent('Agent B', 'agentB@example.com');

        // Give agent A 2 open tickets
        for ($i = 0; $i < 2; $i++) {
            Ticket::create([
                'subject' => "Agent A ticket $i",
                'description' => 'Open ticket.',
                'customer_id' => $this->customer->id,
                'status_id' => $this->openStatus->id,
                'priority' => 'normal',
                'source' => 'portal',
                'assignee_id' => $agentA->id,
            ]);
        }

        // The new ticket should be assigned to agent B (fewer tickets)
        $newTicket = Ticket::create([
            'subject' => 'New unassigned ticket',
            'description' => 'Assign me.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        $service = app(AssignmentService::class);
        $assignment = $service->autoAssignByWorkload($newTicket);

        // Should be assigned to agent B (workload=0)
        $this->assertNotNull($assignment);
        $this->assertEquals($agentB->id, $assignment->agent_id);
    }

    public function test_auto_assign_returns_null_when_no_agents_available(): void
    {
        $ticket = Ticket::create([
            'subject' => 'No agent ticket',
            'description' => 'Nobody to assign to.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        $service = app(AssignmentService::class);
        // No agents exist with helpdesk-agent role
        $result = $service->autoAssignByWorkload($ticket);

        $this->assertNull($result);
    }

    public function test_assignment_service_assigns_ticket_to_agent(): void
    {
        $agent = $this->createAgent('Assign Agent', 'assignagent@example.com');

        $ticket = Ticket::create([
            'subject' => 'Manual assign ticket',
            'description' => 'Assign this.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        $this->actingAs($agent);

        $service = app(AssignmentService::class);
        $assignment = $service->assignTicket($ticket, $agent->id);

        $this->assertNotNull($assignment);
        $this->assertEquals($agent->id, $assignment->agent_id);

        $ticket->refresh();
        $this->assertEquals($agent->id, $ticket->assignee_id);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createAgent(string $name, string $email): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
        ]);

        // Give the user the helpdesk-agent role via Spatie (if available)
        try {
            $user->assignRole('helpdesk-agent');
        } catch (\Throwable) {
            // Role may not exist in test env — skip role assignment
        }

        return $user;
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
