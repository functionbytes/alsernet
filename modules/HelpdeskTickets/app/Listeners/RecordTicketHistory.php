<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * Registra en el historial los eventos que TicketObserver no ve (cierre,
 * reapertura, SLA). Síncrono (antes ShouldQueue en helpdesk-audit): en cola auth()->id() es
 * siempre null y todas las filas salían sin autor. Es un INSERT; no compensa
 * perder quién cerró o reabrió el ticket.
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

        if (! $action || ! $ticketId) {
            return;
        }

        // Al ser síncrono, un fallo aquí no puede tumbar el cierre o la
        // reapertura que lo disparó: el historial es secundario.
        try {
            TicketHistory::create([
                'ticket_id' => $ticketId,
                'user_id' => auth()->id(),
                'action_type' => $action,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'metadata' => $description ? ['description' => $description] : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('RecordTicketHistory failed', [
                'event' => get_class($event),
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
