<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MessageAdded;
use Modules\Helpdesk\Events\TicketStatusChanged;

/**
 * Update ticket last activity timestamp
 */
class UpdateTicketLastActivity
{
    /**
     * Handle the event
     */
    public function handle(MessageAdded|TicketStatusChanged $event): void
    {
        $ticket = match (true) {
            $event instanceof MessageAdded => $event->message->ticket,
            $event instanceof TicketStatusChanged => $event->ticket,
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
