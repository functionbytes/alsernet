<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Models\TicketSideConversationMessage;

/**
 * Avisa al compañero invitado a un side conversation de que hay un mensaje nuevo
 * en el hilo lateral de un ticket.
 */
class TicketSideConversationMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TicketSideConversation $sideConversation,
        private readonly TicketSideConversationMessage $message,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $ticket = $this->sideConversation->ticket;

        return [
            'type' => 'ticket_side_conversation_message',
            'title' => 'Nuevo mensaje en conversación lateral',
            'message' => $this->sideConversation->subject,
            'entity_id' => $this->sideConversation->id,
            'ticket_id' => $this->sideConversation->ticket_id,
            'action_url' => $ticket ? route('manager.helpdesk.tickets.show', $ticket) : null,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
