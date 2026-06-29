<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class TicketNotesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create();

        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'notes-test-customer@example.com'],
            ['name' => 'Notes Test Customer']
        );
    }

    public function test_can_create_pinned_note(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.notes.store', $ticket), [
                'ticket_id' => $ticket->id,
                'body' => 'This note should be pinned.',
                'is_pinned' => true,
                'color' => 'yellow',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_notes', [
            'ticket_id' => $ticket->id,
            'is_pinned' => true,
        ], 'helpdesk');
    }

    public function test_show_note_returns_404_when_note_belongs_to_other_ticket(): void
    {
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket();

        $note = TicketNote::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $this->agent->id,
            'body' => 'Note on another ticket.',
            'is_pinned' => false,
            'color' => 'yellow',
        ]);

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.notes.show', [$ticket, $note]))
            ->assertNotFound();
    }

    public function test_pin_toggles_pinned_status(): void
    {
        $ticket = $this->createTicket();
        $note = $this->createNote($ticket, ['is_pinned' => false]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.notes.pin', [$ticket, $note]))
            ->assertOk()
            ->assertJsonFragment(['is_pinned' => true]);

        $this->assertTrue($note->fresh()->is_pinned);
    }

    public function test_pin_note_from_other_ticket_returns_404(): void
    {
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket();
        $note = $this->createNote($otherTicket);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.notes.pin', [$ticket, $note]))
            ->assertNotFound();
    }

    public function test_change_color_accepts_valid_color(): void
    {
        $ticket = $this->createTicket();
        $note = $this->createNote($ticket, ['color' => 'yellow']);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.notes.color', [$ticket, $note]), [
                'color' => 'blue',
            ])
            ->assertOk()
            ->assertJsonFragment(['color' => 'blue']);

        $this->assertEquals('blue', $note->fresh()->color);
    }

    public function test_change_color_rejects_invalid_color(): void
    {
        $ticket = $this->createTicket();
        $note = $this->createNote($ticket, ['color' => 'yellow']);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.notes.color', [$ticket, $note]), [
                'color' => 'hotpink',
            ])
            ->assertStatus(422);
    }

    public function test_destroy_requires_delete_permission(): void
    {
        $unauthorized = User::factory()->create();
        $ticket = $this->createTicket();
        $note = $this->createNote($ticket);

        $this->actingAs($unauthorized)
            ->deleteJson(route('manager.helpdesk.tickets.notes.destroy', [$ticket, $note]))
            ->assertForbidden();
    }

    public function test_destroy_soft_deletes_note(): void
    {
        $ticket = $this->createTicket();
        $note = $this->createNote($ticket);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.notes.destroy', [$ticket, $note]))
            ->assertOk();

        $this->assertSoftDeleted('helpdesk_ticket_notes', ['id' => $note->id], 'helpdesk');
    }

    public function test_store_requires_authentication(): void
    {
        $ticket = $this->createTicket();

        $this->postJson(route('manager.helpdesk.tickets.notes.store', $ticket), [
            'ticket_id' => $ticket->id,
            'body' => 'Unauthenticated note.',
        ])
            ->assertUnauthorized();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Note test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function createNote(Ticket $ticket, array $overrides = []): TicketNote
    {
        return TicketNote::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $this->agent->id,
            'body' => 'A test note.',
            'is_pinned' => false,
            'color' => 'yellow',
        ], $overrides));
    }
}
