<?php

namespace Modules\HelpdeskTickets\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is assigned to an agent
 *
 * ShouldBroadcast (8-sep-2026): asignar —a mano, al responder, por
 * automatización o en bloque— solo actualizaba el panel "Asignado a" de quien
 * disparaba la acción. Otro agente con el mismo ticket abierto se quedaba
 * viendo al dueño anterior (o el banner de autoasignación sobre un ticket que
 * ya cogió otro) hasta que recargaba a mano. Dos canales:
 *
 *  - ticket.{id} (presencia, mismo que ya usa MessageAdded): a quien tiene
 *    ESTE ticket abierto — repinta "Asignado a", avisa si se lo acaban de
 *    quitar y cierra el modal de asignar si dos agentes competían por él.
 *  - helpdesk.tickets (privado, mismo que ya usa TicketCreated): a toda la
 *    bandeja, para el aviso de "hay cambios" de la lista — que "Sin asignar"
 *    ya no cuenta este ticket, sin tocar la fila a ciegas.
 */
class TicketAssigned implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(public Ticket $ticket, public User $agent) {}

    /**
     * @return array<int, PresenceChannel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.tickets'),
            new PresenceChannel('ticket.'.$this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'assigned';
    }

    /**
     * assignee: mismo par {id, name} que ya arma openAssignModal() al asignar
     * a mano (apply() en tickets-app.js) y que expone toListRow()->assignee —
     * una sola forma del dato, no dos que puedan desalinearse. ticket_id: solo
     * lo necesita el listener de la lista (helpdesk.tickets no está scopeado
     * a un ticket como sí lo está el canal de presencia).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'assignee' => [
                'id' => $this->agent->id,
                'name' => $this->agent->fullName() ?: 'Agente',
            ],
        ];
    }
}
