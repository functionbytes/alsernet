<?php

namespace Modules\HelpdeskAgents\Listeners;

use Modules\HelpdeskAgents\Jobs\GenerateTicketSummaryJob;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * The escalation engine (EscalationService) does not emit a dedicated event —
 * its public contract is the `escalated` row it writes to the ticket history.
 * We hook the Eloquent `created` event of TicketHistory (registered in
 * HelpdeskAgentsServiceProvider) so escalations trigger an AI summary without
 * touching EscalationService itself.
 */
class QueueTicketSummaryOnEscalation
{
    public function handle(TicketHistory $history): void
    {
        if ($history->action_type !== 'escalated') {
            return;
        }

        if (! config('helpdeskagents.ticket_ai.summaries_enabled', true)) {
            return;
        }

        GenerateTicketSummaryJob::dispatch($history->ticket_id, 'escalated');
    }
}
