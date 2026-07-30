<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;

/**
 * Update ticket last activity timestamp
 */
class UpdateTicketLastActivity
{
    /**
     * Handle the event
     */
    public function handle(MessageAdded|TicketStatusChanged|TicketCreated $event): void
    {
        $ticket = match (true) {
            $event instanceof MessageAdded => $event->message->ticket,
            $event instanceof TicketStatusChanged => $event->ticket,
            $event instanceof TicketCreated => $event->ticket,
        };

        $ticket->update([
            'last_activity_at' => now(),
        ]);

        Log::info('Ticket last activity updated', [
            'ticket_id' => $ticket->id,
            'last_activity_at' => $ticket->last_activity_at,
        ]);
    }
}
