<?php

namespace Modules\HelpdeskTickets\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketMessage;

/**
 * Event fired when a message is added to a ticket.
 *
 * Se despacha con TicketMessage (TicketService::addMessage) o con TicketItem
 * (TicketMessagingController storeMessage/bulkReply); antes el tipo estricto
 * TicketMessage rompía con TypeError cualquier respuesta de manager. Ambos
 * modelos exponen ticket(), user_id e is_internal, que es lo que consumen los
 * listeners. $item es un alias porque SendCustomerReplyNotification accede a
 * $event->item.
 *
 * ShouldBroadcast (añadido 4-sep-2026): antes un correo entrante real
 * (FetchTicketEmailsJob) creaba el TicketItem pero, si el agente ya tenía el
 * ticket abierto, no aparecía hasta recargar la página a mano — no había
 * ningún mecanismo de refresco en vivo del hilo. Se transmite en el mismo
 * canal de presencia ticket.{id} que ya usan TicketViewing/TicketTyping y
 * también en helpdesk.tickets para que el listado se actualice sin esperar
 * al polling. Ambos canales están autorizados: el primero por TicketPolicy y
 * el segundo por el permiso de ver la bandeja. El payload es mínimo a
 * propósito: el frontend, al recibirlo, vuelve a pedir
 * TicketDetailDataController::data() completo (fetchDetailData(), ya usado
 * en ~15 sitios) en vez de reconstruir aquí el mapeo completo del hilo
 * (traducción, HTML purificado, adjuntos…), que ya existe una sola vez en el
 * controller.
 */
class MessageAdded implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public TicketMessage|TicketItem $item;

    public function __construct(public TicketMessage|TicketItem $message)
    {
        $this->item = $message;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('ticket.'.$this->item->ticket_id),
            new PrivateChannel('helpdesk.tickets'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.added';
    }

    /**
     * Mínimo a propósito — ver docblock de la clase.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->item->ticket_id,
            'item_id' => $this->item->id,
            'is_internal' => (bool) $this->item->is_internal,
        ];
    }
}
