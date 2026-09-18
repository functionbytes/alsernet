<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class MessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationItem $message,
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        $preview = mb_substr(strip_tags($this->message->body ?? ''), 0, 100);

        return [
            'type' => 'helpdesk_message_received',
            'title' => 'Nuevo mensaje del cliente',
            'message' => "Mensaje en conversacion #{$this->conversation->id}: {$preview}",
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.show', $this->conversation),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
