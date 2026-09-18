<?php

namespace Modules\HelpdeskAgents\Listeners;

use Modules\HelpdeskAgents\Jobs\GenerateTicketSummaryJob;
use Modules\HelpdeskTickets\Events\TicketAssigned;

/**
 * When a ticket is assigned to an agent, queue an AI summary so the agent
 * gets an internal note with the context of the case.
 */
class QueueTicketSummaryOnAssigned
{
    public function handle(TicketAssigned $event): void
    {
        if (! config('helpdeskagents.ticket_ai.summaries_enabled', true)) {
            return;
        }

        GenerateTicketSummaryJob::dispatch($event->ticket->id, 'assigned');
    }
}
