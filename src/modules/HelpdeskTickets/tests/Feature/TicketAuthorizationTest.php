<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

/**
 * Verify that users cannot access tickets belonging to other customers.
 */
class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private TicketStatus $openStatus;

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
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $owner = $this->makeCustomer('owner@example.com');
        $intruder = $this->makeCustomer('intruder@example.com');

        $ticket = $this->makeTicket($owner);

        $this->withSession(['portal_customer_id' => $intruder->id])
            ->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertNotFound();
    }

    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $owner = $this->makeCustomer('owner2@example.com');
        $intruder = $this->makeCustomer('intruder2@example.com');

        $ticket = $this->makeTicket($owner);

        $this->withSession(['portal_customer_id' => $intruder->id])
            ->post(route('portal.tickets.reply', $ticket->ticket_number), [
                'message' => 'Attempting to inject a reply.',
            ])
            ->assertNotFound();
    }

    public function test_customer_cannot_rate_another_customers_ticket(): void
    {
        $owner = $this->makeCustomer('owner3@example.com');
        $intruder = $this->makeCustomer('intruder3@example.com');

        $ticket = $this->makeTicket($owner, ['closed_at' => now()]);

        $this->withSession(['portal_customer_id' => $intruder->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 5,
            ])
            ->assertNotFound();
    }

    public function test_unauthenticated_user_is_redirected_from_portal(): void
    {
        $customer = $this->makeCustomer('anon@example.com');
        $ticket = $this->makeTicket($customer);

        $this->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertRedirect(route('portal.login'));
    }

    public function test_owner_can_view_their_own_ticket(): void
    {
        $customer = $this->makeCustomer('self@example.com');
        $ticket = $this->makeTicket($customer);

        $this->withSession(['portal_customer_id' => $customer->id])
            ->get(route('portal.tickets.show', $ticket->ticket_number))
            ->assertOk();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTicket(Customer $customer, array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ], $overrides));
    }

    private function makeCustomer(string $email): Customer
    {
        return Customer::create([
            'name' => 'Customer '.uniqid(),
            'email' => $email,
        ]);
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
