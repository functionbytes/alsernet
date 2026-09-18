<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;

/**
 * Recalculate the ticket SLA due dates when its priority changes.
 *
 * Previously this wrote phantom columns (due_at, response_time) using the
 * deprecated SlaPolicy keyed by a non-existent string `priority` column, so it
 * failed silently. It now delegates to the canonical Ticket::calculateSlaDueDates(),
 * which uses TicketSlaPolicy + priority multipliers and writes the real
 * sla_first_response_due_at / sla_resolution_due_at columns.
 */
class RecalculateSlaPolicy
{
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;

        if (! $ticket->wasChanged('priority')) {
            return;
        }

        if (! $ticket->slaPolicy) {
            return;
        }

        $ticket->calculateSlaDueDates();

        Log::info('Ticket SLA due dates recalculated after priority change', [
            'ticket_id' => $ticket->id,
            'priority' => $ticket->priority,
            'sla_policy_id' => $ticket->sla_policy_id,
            'sla_first_response_due_at' => $ticket->sla_first_response_due_at,
            'sla_resolution_due_at' => $ticket->sla_resolution_due_at,
        ]);
    }
}
