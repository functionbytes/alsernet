<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class BulkTicketOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->manager = User::factory()->create();

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_bulk_close_requires_authentication(): void
    {
        $ticket = $this->createTestTicket();

        $this->post(route('manager.helpdesk.tickets.bulk'), [
            'ticket_ids' => [$ticket->id],
            'action' => 'close',
        ])->assertRedirect('/login');
    }

    public function test_bulk_close_marks_tickets_as_closed(): void
    {
        $ticketA = $this->createTestTicket(['subject' => 'Ticket A']);
        $ticketB = $this->createTestTicket(['subject' => 'Ticket B']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'action' => 'close',
            ])->assertRedirect(route('manager.helpdesk.tickets.index'));

        $this->assertNotNull(Ticket::find($ticketA->id)->closed_at);
        $this->assertNotNull(Ticket::find($ticketB->id)->closed_at);
    }

    public function test_bulk_assign_sets_assigned_to_on_all_tickets(): void
    {
        $agent = User::factory()->create();
        $ticketA = $this->createTestTicket();
        $ticketB = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
                'action' => 'assign',
                'agent_id' => $agent->id,
            ])->assertRedirect(route('manager.helpdesk.tickets.index'));

        $this->assertEquals($agent->id, Ticket::find($ticketA->id)->assigned_to);
        $this->assertEquals($agent->id, Ticket::find($ticketB->id)->assigned_to);
    }

    public function test_bulk_delete_soft_deletes_tickets(): void
    {
        $ticket = $this->createTestTicket(['subject' => 'To be deleted']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'delete',
            ])->assertRedirect(route('manager.helpdesk.tickets.index'));

        $this->assertSoftDeleted('helpdesk_tickets', ['id' => $ticket->id], 'helpdesk');
    }

    public function test_bulk_reopen_clears_closed_at(): void
    {
        $ticket = $this->createTestTicket(['closed_at' => now()]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'reopen',
            ])->assertRedirect(route('manager.helpdesk.tickets.index'));

        $this->assertNull(Ticket::find($ticket->id)->closed_at);
    }

    public function test_validation_fails_when_ticket_ids_is_empty(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [],
                'action' => 'close',
            ])->assertSessionHasErrors(['ticket_ids']);
    }

    public function test_validation_fails_when_action_is_invalid(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'explode',
            ])->assertSessionHasErrors(['action']);
    }

    public function test_validation_fails_when_more_than_100_tickets_selected(): void
    {
        $ids = range(1, 101);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => $ids,
                'action' => 'close',
            ])->assertSessionHasErrors(['ticket_ids']);
    }

    public function test_bulk_assign_requires_agent_id(): void
    {
        $ticket = $this->createTestTicket();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'ticket_ids' => [$ticket->id],
                'action' => 'assign',
                // agent_id intentionally omitted
            ])->assertSessionHasErrors(['agent_id']);
    }

    public function test_validation_fails_when_ticket_ids_missing_entirely(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.bulk'), [
                'action' => 'close',
            ])->assertSessionHasErrors(['ticket_ids']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTestTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
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
