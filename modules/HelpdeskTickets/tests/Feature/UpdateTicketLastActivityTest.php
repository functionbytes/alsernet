<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\UpdateTicketLastActivity;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Regression test: `last_activity_at` was missing from Ticket::$fillable even
 * though the column exists (with its own index). update(['last_activity_at' =>
 * now()]) silently dropped the value — no exception, the field just never
 * persisted.
 */
class UpdateTicketLastActivityTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_handling_ticket_created_persists_last_activity_at(): void
    {
        $ticket = Ticket::factory()->create(['last_activity_at' => null]);

        (new UpdateTicketLastActivity)->handle(new TicketCreated($ticket));

        $this->assertNotNull($ticket->fresh()->last_activity_at);
    }
}
