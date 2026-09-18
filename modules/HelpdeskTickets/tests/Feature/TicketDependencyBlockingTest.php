<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Dependencias entre tickets: un ticket no puede cerrarse si un ticket
 * bloqueante sigue abierto, salvo que se fuerce (force=1).
 */
class TicketDependencyBlockingTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $status;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.tickets.update');
    }

    public function test_cannot_close_a_ticket_with_an_open_blocker(): void
    {
        $ticket = $this->ticket();
        $blocker = $this->ticket();
        // $ticket está bloqueado por $blocker (abierto).
        TicketLink::create([
            'ticket_id' => $ticket->id,
            'linked_ticket_id' => $blocker->id,
            'link_type' => 'blocked_by',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket))
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertNull($ticket->fresh()->closed_at);
    }

    public function test_can_close_once_the_blocker_is_closed(): void
    {
        $ticket = $this->ticket();
        $blocker = $this->ticket(['closed_at' => now()]);
        TicketLink::create([
            'ticket_id' => $ticket->id,
            'linked_ticket_id' => $blocker->id,
            'link_type' => 'blocked_by',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket))
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_force_closes_despite_an_open_blocker(): void
    {
        $ticket = $this->ticket();
        $blocker = $this->ticket();
        TicketLink::create([
            'ticket_id' => $ticket->id,
            'linked_ticket_id' => $blocker->id,
            'link_type' => 'blocked_by',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), ['force' => 1])
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_related_link_does_not_block_closing(): void
    {
        $ticket = $this->ticket();
        $other = $this->ticket();
        TicketLink::create([
            'ticket_id' => $ticket->id,
            'linked_ticket_id' => $other->id,
            'link_type' => 'related',
            'created_by' => $this->agent->id,
        ]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket))
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Dependency ticket',
            'description' => 'x',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
