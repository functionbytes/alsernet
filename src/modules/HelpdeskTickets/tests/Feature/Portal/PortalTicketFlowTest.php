<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class PortalTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Event::fake();

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

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            [
                'name' => 'Closed',
                'color' => '#aaaaaa',
                'is_open' => false,
                'is_default' => false,
                'order' => 99,
            ]
        );

        $this->customer = Customer::factory()->create();
    }

    // ─── Ticket list ──────────────────────────────────────────────────────────

    public function test_customer_can_view_their_own_tickets(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets'))
            ->assertOk()
            ->assertSee($ticket->subject);
    }

    public function test_customer_cannot_see_other_customers_tickets_in_list(): void
    {
        $other = Customer::factory()->create();
        $this->createTicket($other, ['subject' => 'Other customer private ticket']);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets'))
            ->assertOk()
            ->assertDontSee('Other customer private ticket');
    }

    public function test_unauthenticated_visitor_is_redirected_from_ticket_list(): void
    {
        $this->get(route('portal.tickets'))
            ->assertRedirect(route('portal.login'));
    }

    public function test_banned_customer_is_logged_out_and_redirected(): void
    {
        $banned = Customer::factory()->banned()->create();

        $this->withSession(['portal_customer_id' => $banned->id])
            ->get(route('portal.tickets'))
            ->assertRedirect(route('portal.login'))
            ->assertSessionHasErrors(['email']);
    }

    // ─── Ticket detail ────────────────────────────────────────────────────────

    public function test_customer_can_view_their_own_ticket_detail(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertOk()
            ->assertSee($ticket->subject);
    }

    public function test_ticket_detail_shows_public_messages(): void
    {
        $ticket = $this->createTicket($this->customer);

        TicketItem::create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => 'Visible customer message',
            'is_internal' => false,
        ]);

        TicketItem::create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => 'Agent private note',
            'is_internal' => true,
        ]);

        $response = $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.show', $ticket->ticket_number));

        $response->assertOk()
            ->assertSee('Visible customer message')
            ->assertDontSee('Agent private note');
    }

    public function test_customer_cannot_view_another_customers_ticket_detail(): void
    {
        $other = Customer::factory()->create();
        $otherTicket = $this->createTicket($other);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.show', $otherTicket->ticket_number))
            ->assertNotFound();
    }

    public function test_ticket_detail_returns_404_for_nonexistent_number(): void
    {
        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.show', 'TCK-9999-99999'))
            ->assertNotFound();
    }

    public function test_unauthenticated_visitor_is_redirected_from_ticket_detail(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertRedirect(route('portal.login'));
    }

    // ─── Reply to ticket ──────────────────────────────────────────────────────

    public function test_customer_can_reply_to_their_open_ticket(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.reply', $ticket->ticket_number), [
                'message' => 'This is my follow-up message.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Your reply has been sent.');

        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $ticket->id,
            'body' => 'This is my follow-up message.',
            'is_internal' => 0,
            'author_id' => $this->customer->id,
        ], 'helpdesk');
    }

    public function test_reply_requires_message_field(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.reply', $ticket->ticket_number), [
                'message' => '',
            ])
            ->assertSessionHasErrors(['message']);
    }

    public function test_reply_rejects_message_exceeding_max_length(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.reply', $ticket->ticket_number), [
                'message' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors(['message']);
    }

    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $other = Customer::factory()->create();
        $otherTicket = $this->createTicket($other);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.reply', $otherTicket->ticket_number), [
                'message' => 'Unauthorized reply attempt.',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'ticket_id' => $otherTicket->id,
            'body' => 'Unauthorized reply attempt.',
        ], 'helpdesk');
    }

    public function test_unauthenticated_visitor_cannot_submit_reply(): void
    {
        $ticket = $this->createTicket($this->customer);

        $this->post(route('portal.tickets.reply', $ticket->ticket_number), [
            'message' => 'Should not be saved.',
        ])
            ->assertRedirect(route('portal.login'));

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'ticket_id' => $ticket->id,
            'body' => 'Should not be saved.',
        ], 'helpdesk');
    }

    // ─── Create ticket ────────────────────────────────────────────────────────

    public function test_customer_can_access_create_ticket_form(): void
    {
        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.create'))
            ->assertOk();
    }

    public function test_customer_can_submit_a_new_ticket(): void
    {
        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.store'), [
                'subject' => 'Need help with my order',
                'description' => 'I placed an order last week and have not received a confirmation email.',
                'priority' => 'normal',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Your ticket has been created.');

        $this->assertDatabaseHas('helpdesk_tickets', [
            'subject' => 'Need help with my order',
            'customer_id' => $this->customer->id,
            'source' => 'portal',
        ], 'helpdesk');
    }

    public function test_ticket_creation_requires_subject(): void
    {
        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.store'), [
                'description' => 'Missing subject field.',
            ])
            ->assertSessionHasErrors(['subject']);
    }

    public function test_ticket_creation_requires_description(): void
    {
        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.store'), [
                'subject' => 'Valid subject',
            ])
            ->assertSessionHasErrors(['description']);
    }

    public function test_unauthenticated_visitor_cannot_create_ticket(): void
    {
        $this->post(route('portal.tickets.store'), [
            'subject' => 'Ghost ticket',
            'description' => 'Should not be stored.',
        ])
            ->assertRedirect(route('portal.login'));

        $this->assertDatabaseMissing('helpdesk_tickets', [
            'subject' => 'Ghost ticket',
        ]);
    }

    public function test_unauthenticated_visitor_is_redirected_from_create_form(): void
    {
        $this->get(route('portal.tickets.create'))
            ->assertRedirect(route('portal.login'));
    }

    // ─── Closed ticket ────────────────────────────────────────────────────────

    public function test_customer_can_view_closed_ticket_detail(): void
    {
        $ticket = $this->createTicket($this->customer, [
            'status_id' => $this->closedStatus->id,
            'closed_at' => now()->subDay(),
        ]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertOk()
            ->assertSee($ticket->subject);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(Customer $customer, array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket subject',
            'description' => 'Test ticket description.',
            'customer_id' => $customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ], $overrides));
    }
}
