<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketWatcher;

/**
 * Extraído de TicketLifecycleController::merge() para poder reutilizarlo
 * desde la fusión individual (ficha completa) Y desde la acción masiva
 * "Vincular a un ticket" del mockup ("ve-mail-bulk", modal 13) — antes solo
 * existía como método de instancia del controller, sin forma de invocarlo
 * más que a través de esa única ruta HTTP.
 */
class TicketMergeService
{
    /**
     * Mueve TODO lo del ticket origen al destino (hilo, historial, correos,
     * tiempos, notas, comentarios, seguidores, enlaces) y cierra/borra el
     * origen. No comprueba permisos ni valida — eso es responsabilidad del
     * llamador (TicketPolicy::merge/update ya se comprobaba en el controller
     * individual; la acción masiva comprueba lo mismo por cada ticket).
     */
    public function merge(Ticket $ticket, Ticket $targetTicket): void
    {
        DB::transaction(function () use ($ticket, $targetTicket) {
            $ticket->items()->update(['ticket_id' => $targetTicket->id]);

            // Migrar el resto de datos asociados para no perderlos al borrar el
            // ticket origen: historial, emails, notas, comentarios y tiempos.
            // history() se actualiza a nivel de query (los modelos TicketHistory
            // son inmutables a nivel de instancia, pero aquí solo se reapunta
            // la FK, no se reescribe el registro).
            $ticket->history()->update(['ticket_id' => $targetTicket->id]);
            $ticket->mails()->update(['ticket_id' => $targetTicket->id]);
            $ticket->timeEntries()->update(['ticket_id' => $targetTicket->id]);
            TicketNote::withTrashed()->where('ticket_id', $ticket->id)->update(['ticket_id' => $targetTicket->id]);
            TicketComment::withTrashed()->where('ticket_id', $ticket->id)->update(['ticket_id' => $targetTicket->id]);

            $ticket->watchers()->each(function (TicketWatcher $watcher) use ($targetTicket) {
                TicketWatcher::firstOrCreate([
                    'ticket_id' => $targetTicket->id,
                    'user_id' => $watcher->user_id,
                ]);
            });

            // Reapuntar enlaces del origen al destino, descartando los que
            // quedarían auto-enlazados o duplicados en el destino.
            $ticket->links()->get()->each(function (TicketLink $link) use ($targetTicket) {
                $duplicate = $link->linked_ticket_id === $targetTicket->id
                    || TicketLink::where('ticket_id', $targetTicket->id)
                        ->where('linked_ticket_id', $link->linked_ticket_id)
                        ->exists();

                $duplicate ? $link->delete() : $link->update(['ticket_id' => $targetTicket->id]);
            });

            $ticket->linkedBy()->get()->each(function (TicketLink $link) use ($targetTicket) {
                $duplicate = $link->ticket_id === $targetTicket->id
                    || TicketLink::where('ticket_id', $link->ticket_id)
                        ->where('linked_ticket_id', $targetTicket->id)
                        ->exists();

                $duplicate ? $link->delete() : $link->update(['linked_ticket_id' => $targetTicket->id]);
            });

            $targetTicket->items()->create([
                'type' => 'system',
                'body' => "Merged from #{$ticket->ticket_number}",
                'metadata' => ['merged_from_ticket_id' => $ticket->id],
            ]);

            $ticket->close();
            $ticket->delete();
        });
    }
}
