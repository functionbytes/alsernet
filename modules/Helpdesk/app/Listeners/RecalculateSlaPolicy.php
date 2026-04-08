<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\TicketStatusChanged;
use Modules\Helpdesk\Models\SlaPolicy;

/**
 * Recalculate SLA policy when ticket priority changes
 */
class RecalculateSlaPolicy
{
    /**
     * Handle the event
     */
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;

        if ($event->oldStatus !== $event->newStatus && in_array('priority', array_keys($ticket->getDirty()))) {
            $slaPolicy = SlaPolicy::where('priority', $ticket->priority)
                ->where('is_active', true)
                ->first();

            if ($slaPolicy) {
                $dueAt = now()->addMinutes($slaPolicy->response_time);

                $ticket->update([
                    'sla_policy_id' => $slaPolicy->id,
                    'due_at' => $dueAt,
                ]);

                Log::info('SLA policy recalculated due to priority change', [
                    'ticket_id' => $ticket->id,
                    'old_priority' => $event->oldStatus,
                    'new_priority' => $ticket->priority,
                    'sla_policy_id' => $slaPolicy->id,
                    'new_due_at' => $dueAt,
                ]);
            }
        }
    }
}
