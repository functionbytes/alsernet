<?php

namespace Modules\HelpdeskTickets\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Alguien empieza o deja de seguir un ticket (TicketLifecycleController::
 * watch()/unwatch()).
 *
 * Antes esto era completamente silencioso: ni un evento, ni un broadcast.
 * Otro agente con el mismo ticket abierto se quedaba viendo el contador
 * "seguidores: N" de la tarjeta "Asignado a" desactualizado hasta recargar a
 * mano — el mismo problema que tenía la asignación antes de TicketAssigned
 * (8-sep-2026). Mismo canal de presencia ticket.{id}, mismo patrón: payload
 * vacío a propósito, el frontend ya tiene fetchDetailData() para traer el
 * dato real (watchers/watchers_count) en vez de reconstruirlo aquí.
 *
 * No hace falta el canal helpdesk.tickets (como sí necesita TicketAssigned
 * para el aviso de la lista): ninguna fila del listado ni contador de pestaña
 * depende de los seguidores, solo la tarjeta de detalle de ESTE ticket.
 */
class TicketWatcherChanged implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('ticket.'.$this->ticket->id)];
    }

    public function broadcastAs(): string
    {
        return 'watchers.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
