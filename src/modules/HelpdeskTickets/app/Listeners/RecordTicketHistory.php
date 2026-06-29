<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * Record all ticket events in the history table
 */
class RecordTicketHistory implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-audit';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

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
                $oldValue = $event->previousStatus->id,
                $newValue = $event->newStatus->id,
                $description = "Estado cambiado de {$event->previousStatus->name} a {$event->newStatus->name}",
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

    public function failed(object $event, \Throwable $e): void
    {
        Log::error('RecordTicketHistory listener failed', [
            'event' => get_class($event),
            'error' => $e->getMessage(),
        ]);
    }
}
