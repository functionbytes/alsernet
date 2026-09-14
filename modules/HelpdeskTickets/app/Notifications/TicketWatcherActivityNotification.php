<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Aviso a quien SIGUE un ticket sin ser su asignatario.
 *
 * La tabla helpdesk_ticket_watchers y el modal "Seguidores" existían desde el
 * principio, pero ningún listener les mandaba nada: seguir un ticket no tenía
 * ningún efecto observable. Esta notificación es lo que hace que seguir
 * signifique algo.
 */
class TicketWatcherActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'customer_reply'|'internal_note'  $kind
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $kind,
        public readonly ?string $actorName = null,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $esRespuesta = $this->kind === 'customer_reply';

        return [
            'type' => 'ticket_watcher_activity',
            'title' => $esRespuesta
                ? 'Respuesta del cliente en un ticket que sigues'
                : 'Nota interna en un ticket que sigues',
            'message' => $esRespuesta
                ? "El cliente respondió en el ticket #{$this->ticket->ticket_number}"
                : ($this->actorName
                    ? "{$this->actorName} escribió una nota en el ticket #{$this->ticket->ticket_number}"
                    : "Nueva nota interna en el ticket #{$this->ticket->ticket_number}"),
            'icon' => $esRespuesta ? 'fas fa-reply' : 'fas fa-lock',
            'color' => 'info',
            'action_url' => route('manager.helpdesk.tickets.index').'?ticket='.$this->ticket->id,
            'action_text' => 'Ver ticket',
            'priority' => 'normal',
        ];
    }
}
