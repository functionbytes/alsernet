<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Agents;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class AgentTicketsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    private Customer $customer;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.create', 'guard_name' => 'web']);

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo([
            'helpdesk.tickets.view',
            'helpdesk.tickets.update',
            'helpdesk.tickets.create',
        ]);

        // Las rutas agent.* llevan middleware role:helpdesk-agent|super-admin|
        // super-settings|manager — el permiso solo no basta.
        $this->seedHelpdeskRoles();
        $this->agent->assignRole('helpdesk-agent');

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'agent-test-customer@example.com'],
            ['name' => 'Agent Test Customer']
        );
    }

    public function test_agent_sees_only_assigned_tickets_on_index(): void
    {
        $assignedTicket = $this->createTicket(['assignee_id' => $this->agent->id, 'subject' => 'Assigned ticket']);
        $otherTicket = $this->createTicket(['assignee_id' => null, 'subject' => 'Not assigned']);

        $this->actingAs($this->agent)
            ->get(route('agent.helpdesk.tickets.index'))
            ->assertOk()
            ->assertSee('Assigned ticket')
            ->assertDontSee('Not assigned');
    }

    public function test_agent_without_view_permission_is_forbidden_on_index(): void
    {
        $noPermissionUser = User::factory()->create();

        $this->actingAs($noPermissionUser)
            ->get(route('agent.helpdesk.tickets.index'))
            ->assertForbidden();
    }

    public function test_agent_can_view_assigned_ticket(): void
    {
        $ticket = $this->createTicket(['assignee_id' => $this->agent->id]);

        $this->actingAs($this->agent)
            ->get(route('agent.helpdesk.tickets.show', $ticket))
            ->assertOk();
    }

    public function test_agent_cannot_view_unassigned_ticket_without_global_permission(): void
    {
        $restrictedAgent = User::factory()->create();
        // No permissions at all
        $ticket = $this->createTicket(['assignee_id' => null]);

        $this->actingAs($restrictedAgent)
            ->get(route('agent.helpdesk.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_agent_can_reply_to_assigned_ticket_and_event_fires(): void
    {
        Event::fake([MessageAdded::class]);

        $ticket = $this->createTicket(['assignee_id' => $this->agent->id]);

        $this->actingAs($this->agent)
            ->postJson(route('agent.helpdesk.messages.store', $ticket), [
                'body' => 'Hola, le respondo a su consulta.',
                'is_internal' => false,
            ])
            ->assertCreated();

        Event::assertDispatched(MessageAdded::class);
    }

    public function test_agent_cannot_reply_to_ticket_not_assigned_to_them(): void
    {
        $otherTicket = $this->createTicket(['assignee_id' => null]);

        $agentWithNoPermission = User::factory()->create();
        // Agent has no permission to update tickets not assigned to them

        $this->actingAs($agentWithNoPermission)
            ->postJson(route('agent.helpdesk.messages.store', $otherTicket), [
                'body' => 'Trying to reply to someone else ticket.',
                'is_internal' => false,
            ])
            ->assertForbidden();
    }

    public function test_agent_can_close_assigned_ticket(): void
    {
        Event::fake([TicketStatusChanged::class]);

        $ticket = $this->createTicket(['assignee_id' => $this->agent->id]);

        $this->actingAs($this->agent)
            ->post(route('agent.helpdesk.tickets.close', $ticket))
            ->assertRedirect();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('agent.helpdesk.tickets.index'))
            ->assertRedirect();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Agent test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
