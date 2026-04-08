<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MessageAdded;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Events\SlaWarning;
use Modules\Helpdesk\Events\TicketAssigned;
use Modules\Helpdesk\Events\TicketClosed;
use Modules\Helpdesk\Events\TicketCreated;
use Modules\Helpdesk\Events\TicketReopened;
use Modules\Helpdesk\Events\TicketStatusChanged;
use Modules\Helpdesk\Models\TicketHistory;

/**
 * Record all ticket events in the history table
 */
class RecordTicketHistory
{
    /**
     * Handle the event
     */
    public function handle(object $event): void
    {
        $action = null;
        $ticketId = null;
        $oldValue = null;
        $newValue = null;
        $description = null;

        match (true) {
            $event instanceof TicketCreated => [
                $action = 'ticket_created',
                $ticketId = $event->ticket->id,
                $description = 'Ticket creado',
            ],
            $event instanceof TicketAssigned => [
                $action = 'ticket_assigned',
                $ticketId = $event->ticket->id,
                $newValue = $event->agent->id,
                $description = "Ticket asignado a {$event->agent->name}",
            ],
            $event instanceof TicketStatusChanged => [
                $action = 'status_changed',
                $ticketId = $event->ticket->id,
                $oldValue = $event->oldStatus,
                $newValue = $event->newStatus,
                $description = "Estado cambiado de {$event->oldStatus} a {$event->newStatus}",
            ],
            $event instanceof TicketClosed => [
                $action = 'ticket_closed',
                $ticketId = $event->ticket->id,
                $description = 'Ticket cerrado',
            ],
            $event instanceof TicketReopened => [
                $action = 'ticket_reopened',
                $ticketId = $event->ticket->id,
                $description = 'Ticket reabierto',
            ],
            $event instanceof MessageAdded => [
                $action = 'message_added',
                $ticketId = $event->message->ticket_id,
                $description = 'Mensaje agregado',
            ],
            $event instanceof SlaBreached => [
                $action = 'sla_breached',
                $ticketId = $event->ticket->id,
                $description = 'SLA excedido',
            ],
            $event instanceof SlaWarning => [
                $action = 'sla_warning',
                $ticketId = $event->ticket->id,
                $description = 'Advertencia de SLA próximo a vencer',
            ],
            default => null,
        };

        if ($action && $ticketId) {
            TicketHistory::create([
                'ticket_id' => $ticketId,
                'user_id' => auth()->id(),
                'action' => $action,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'description' => $description,
            ]);

            Log::info('Ticket history recorded', [
                'ticket_id' => $ticketId,
                'action' => $action,
                'user_id' => auth()->id(),
            ]);
        }
    }
}
