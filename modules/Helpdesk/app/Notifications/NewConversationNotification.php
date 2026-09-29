<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Helpdesk\Models\Conversation;

class NewConversationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Conversation $conversation)
    {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_new_conversation',
            'title' => 'Nueva conversacion',
            // strip_tags (29-sep-2026): el asunto lo elige el cliente y el desplegable
            // de notificaciones lo pinta como HTML.
            'message' => "Nueva conversacion #{$this->conversation->id}: ".strip_tags((string) $this->conversation->subject),
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.show', $this->conversation),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
