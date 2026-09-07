<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Notifications\TicketWatcherActivityNotification;

/**
 * Avisa a los seguidores del ticket de la actividad que han pedido ver.
 *
 * Seguir un ticket no producía ningún aviso: se podía añadir y quitar gente
 * del modal "Seguidores" y no pasaba absolutamente nada. Este listener es lo
 * que le da efecto, respetando las dos preferencias de cada seguidor
 * (respuestas del cliente / notas internas).
 */
class NotifyTicketWatchers implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(MessageAdded $event): void
    {
        $item = $event->item;
        $ticket = $item->ticket;

        if (! $ticket) {
            return;
        }

        $esInterno = (bool) $item->is_internal;

        // Un mensaje público escrito por un agente es la respuesta que el
        // propio equipo acaba de mandar: avisar de eso a los seguidores sería
        // ruido. Solo interesan las respuestas del CLIENTE y las notas.
        if (! $esInterno && $item->user_id) {
            return;
        }

        $preferencia = $esInterno ? 'notify_internal_notes' : 'notify_customer_replies';
        $kind = $esInterno ? 'internal_note' : 'customer_reply';

        // sender_name es la columna que TicketItem rellena con quién escribió.
        $actor = $esInterno ? ($item->sender_name ?: null) : null;

        $ticket->watchers()
            ->where($preferencia, true)
            // Nadie se avisa a sí mismo de lo que acaba de escribir.
            ->when($item->user_id, fn ($q) => $q->where('user_id', '!=', $item->user_id))
            ->with('user')
            ->get()
            ->each(function ($watcher) use ($ticket, $kind, $actor) {
                if (! $watcher->user) {
                    return;
                }

                try {
                    $watcher->user->notify(new TicketWatcherActivityNotification($ticket, $kind, $actor));
                } catch (\Throwable $e) {
                    // Un seguidor con la ficha rota no debe impedir que el
                    // resto reciba el aviso.
                    Log::warning('No se pudo avisar a un seguidor del ticket', [
                        'ticket_id' => $ticket->id,
                        'user_id' => $watcher->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }
}
