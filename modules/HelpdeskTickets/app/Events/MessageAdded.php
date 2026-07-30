<?php

namespace Modules\HelpdeskTickets\Events;

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
 */
class MessageAdded
{
    use Dispatchable, SerializesModels;

    public TicketMessage|TicketItem $item;

    public function __construct(public TicketMessage|TicketItem $message)
    {
        $this->item = $message;
    }
}
