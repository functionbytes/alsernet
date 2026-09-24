<?php

namespace Modules\HelpdeskTickets\Listeners;

use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\SlaService;

/**
 * Relojes de respuesta del ticket, en un solo sitio para todas las vías que
 * crean mensajes (panel, macros, API, portal, correo entrante, programados):
 *
 * - Respuesta pública de un agente: marca first_response_at si faltaba y
 *   apaga el plazo de "siguiente respuesta" (ya se respondió).
 * - Mensaje del cliente tras la primera respuesta: abre un plazo nuevo de
 *   "siguiente respuesta". Antes ese plazo se calculaba una sola vez al crear
 *   el ticket (creación + X) y nunca se movía, así que MarkOverdueTickets
 *   marcaba incumplimientos que no lo eran.
 * - Mensaje del cliente en un ticket pospuesto: lo despierta. snoozed_until
 *   solo se limpiaba a mano y la respuesta quedaba escondida hasta la fecha.
 *
 * Síncrono a propósito: el plazo tiene que estar puesto cuando el listado se
 * refresca con el broadcast del mismo evento.
 */
class TrackTicketResponseSla
{
    public function handle(MessageAdded $event): void
    {
        $item = $event->message;

        if (! $item instanceof TicketItem || $item->is_internal || $item->type !== 'message') {
            return;
        }

        $ticket = $item->ticket;

        if (! $ticket) {
            return;
        }

        if ($item->isFromAgent()) {
            $ticket->recordAgentResponse();

            return;
        }

        if (! $item->isFromCustomer()) {
            // Mensajes de sistema (acuse automático, seguimientos): ni son
            // respuesta de agente ni piden una.
            return;
        }

        $changes = [];
        $wakeUp = $ticket->snoozed_until !== null;

        if ($wakeUp) {
            $changes['snoozed_until'] = null;
            $changes['snoozed_by'] = null;
        }

        if ($ticket->first_response_at !== null) {
            $due = $ticket->nextResponseDueFrom(now());

            if ($due !== null) {
                $changes['sla_next_response_due_at'] = $due;
            }
        }

        if ($changes !== []) {
            $ticket->forceFill($changes)->saveQuietly();
        }

        // Posponer puede pausar el SLA (casilla "pausar el SLA mientras está
        // aplazado"); al despertarlo el reloj vuelve a correr, salvo que lo
        // tenga parado el propio estado (eso lo gestiona el cambio de estado).
        if ($wakeUp && $ticket->sla_paused_at !== null && ! $ticket->status?->stops_sla_timer) {
            app(SlaService::class)->resumeSla($ticket);
        }
    }
}
