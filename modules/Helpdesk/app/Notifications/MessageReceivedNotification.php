<?php

namespace Modules\Helpdesk\Notifications;

use App\Notifications\Channels\WebPushChannel;
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
        return ['database', 'broadcast', WebPushChannel::class];
    }

    /**
     * "Ana López · #12493" si el cliente tiene nombre, "Conversación #12493"
     * si no: el agente ve de quién es el mensaje sin abrir la conversación.
     */
    private function label(): string
    {
        $name = trim((string) $this->conversation->customer?->name);

        return $name !== ''
            ? "{$name} · #{$this->conversation->id}"
            : "Conversación #{$this->conversation->id}";
    }

    public function toWebPush(mixed $notifiable): array
    {
        $preview = mb_substr(strip_tags($this->message->body ?? ''), 0, 100);

        return [
            'title' => 'Nuevo mensaje del cliente',
            'body' => "{$this->label()}: {$preview}",
            'url' => route('manager.helpdesk.conversations.show', $this->conversation),
            'tag' => 'helpdesk-conversation-'.$this->conversation->id,
        ];
    }

    public function toArray(mixed $notifiable): array
    {
        $preview = mb_substr(strip_tags($this->message->body ?? ''), 0, 100);

        return [
            'type' => 'helpdesk_message_received',
            'title' => 'Nuevo mensaje del cliente',
            'message' => "{$this->label()}: {$preview}",
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.show', $this->conversation),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
