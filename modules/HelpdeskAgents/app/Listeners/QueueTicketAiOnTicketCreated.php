<?php

namespace Modules\HelpdeskAgents\Listeners;

use Modules\HelpdeskAgents\Jobs\ClassifyTicketJob;
use Modules\HelpdeskAgents\Jobs\DetectTicketLanguageJob;
use Modules\HelpdeskTickets\Events\TicketCreated;

/**
 * Fans out the ticket-creation AI enrichment as queued jobs (never inline in
 * the request): language detection for routing, and LLM auto-classification
 * when the (default-off) feature flag is enabled.
 */
class QueueTicketAiOnTicketCreated
{
    public function handle(TicketCreated $event): void
    {
        $ticketId = $event->ticket->id;

        if (config('helpdeskagents.ticket_ai.language_detection', true)) {
            DetectTicketLanguageJob::dispatch($ticketId);
        }

        if (config('helpdeskagents.ticket_ai.auto_classification', false)) {
            ClassifyTicketJob::dispatch($ticketId);
        }
    }
}
