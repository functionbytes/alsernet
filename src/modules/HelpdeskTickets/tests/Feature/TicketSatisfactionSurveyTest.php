<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Mail\TicketSatisfactionSurveyMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class TicketSatisfactionSurveyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $closedStatus;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            [
                'name' => 'Closed',
                'color' => '#FA896B',
                'is_open' => false,
                'is_default' => false,
                'order' => 10,
            ]
        );

        $this->customer = Customer::create([
            'name' => 'Survey Customer',
            'email' => 'survey@example.com',
        ]);
    }

    public function test_satisfaction_survey_is_queued_when_ticket_closes(): void
    {
        Mail::fake();

        $ticket = Ticket::create([
            'subject' => 'Issue to close',
            'description' => 'Please help.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'closed_at' => now(),
        ]);

        event(new TicketClosed($ticket));

        Mail::assertQueued(TicketSatisfactionSurveyMail::class, function (TicketSatisfactionSurveyMail $mail) use ($ticket): bool {
            return $mail->ticket->id === $ticket->id;
        });
    }

    public function test_survey_is_not_queued_if_ticket_already_rated(): void
    {
        Mail::fake();

        $ticket = Ticket::create([
            'subject' => 'Already rated ticket',
            'description' => 'This ticket was already rated.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'closed_at' => now(),
            'rated_at' => now(),
            'rating' => 5,
        ]);

        event(new TicketClosed($ticket));

        Mail::assertNothingQueued();
    }

    public function test_customer_can_rate_ticket_from_email_link(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Ratable ticket',
            'description' => 'Rate me.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'closed_at' => now(),
        ]);

        $this->get(URL::signedRoute('portal.tickets.rate.email', [
            'ticketNumber' => $ticket->ticket_number,
            'rating' => 4,
        ]))->assertRedirect(route('portal.login'));

        $ticket->refresh();
        $this->assertEquals(4, $ticket->rating);
        $this->assertNotNull($ticket->rated_at);
    }

    public function test_email_rating_link_with_invalid_rating_returns_error(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Test invalid rating',
            'description' => 'Description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'closed_at' => now(),
        ]);

        $this->get(URL::signedRoute('portal.tickets.rate.email', [
            'ticketNumber' => $ticket->ticket_number,
            'rating' => 99,
        ]))->assertRedirect(route('portal.login'));

        // Rating should not have been saved
        $ticket->refresh();
        $this->assertNull($ticket->rating);
    }

    public function test_customer_can_rate_ticket_via_portal(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Post-close rating',
            'description' => 'Rate via portal.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->closedStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'closed_at' => now(),
        ]);

        $this->withSession(['portal_customer_id' => $this->customer->id])
            ->post(route('portal.tickets.rate', $ticket->ticket_number), [
                'rating' => 3,
                'rating_comment' => 'It was okay.',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertEquals(3, $ticket->rating);
        $this->assertEquals('It was okay.', $ticket->rating_comment);
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
