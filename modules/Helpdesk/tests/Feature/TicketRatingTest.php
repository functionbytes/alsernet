<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketStatus;
use Tests\TestCase;

class TicketRatingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->customer = Customer::create([
            'name' => 'Rating Customer',
            'email' => 'rating'.uniqid().'@example.com',
        ]);

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
                'color' => '#6c757d',
                'is_open' => false,
                'is_default' => false,
                'order' => 2,
            ]
        );
    }

    public function test_customer_can_rate_closed_ticket(): void
    {
        $ticket = $this->createTicket(['closed_at' => now(), 'status_id' => $this->closedStatus->id]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 5,
                'rating_comment' => 'Great support!',
            ])
            ->assertRedirect();

        $this->assertEquals(5, $ticket->fresh()->rating);
        $this->assertNotNull($ticket->fresh()->rated_at);
    }

    public function test_customer_cannot_rate_open_ticket(): void
    {
        $ticket = $this->createTicket(['closed_at' => null, 'status_id' => $this->openStatus->id]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 5,
            ])
            ->assertNotFound();
    }

    public function test_customer_cannot_rate_twice(): void
    {
        $ticket = $this->createTicket([
            'closed_at' => now(),
            'status_id' => $this->closedStatus->id,
            'rating' => 3,
            'rated_at' => now()->subDay(),
        ]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 5,
            ])
            ->assertNotFound();

        $this->assertEquals(3, $ticket->fresh()->rating);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $ticket = $this->createTicket(['closed_at' => now(), 'status_id' => $this->closedStatus->id]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 6,
            ])
            ->assertSessionHasErrors(['rating']);
    }

    public function test_rating_comment_is_optional(): void
    {
        $ticket = $this->createTicket(['closed_at' => now(), 'status_id' => $this->closedStatus->id]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 4,
                // No rating_comment
            ])
            ->assertRedirect();

        $this->assertEquals(4, $ticket->fresh()->rating);
        $this->assertNull($ticket->fresh()->rating_comment);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Support ticket',
            'description' => 'Please help.',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
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
