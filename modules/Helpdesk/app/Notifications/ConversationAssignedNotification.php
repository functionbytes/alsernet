<?php

namespace Modules\Helpdesk\Notifications;

use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Helpdesk\Models\Conversation;

class ConversationAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Conversation $conversation)
    {
        $this->onQueue('helpdesk-events');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    /**
     * Push real (funciona con la pestaña/app cerrada) — complementa el
     * broadcast en vivo, que solo llega si el agente ya tiene el panel
     * abierto. WebPushChannel no hace nada si el agente nunca se suscribió
     * (sin filas en push_subscriptions).
     */
    public function toWebPush(mixed $notifiable): array
    {
        return [
            'title' => 'Conversación asignada',
            'body' => "Se te ha asignado la conversación #{$this->conversation->id}: {$this->conversation->subject}",
            'url' => route('manager.helpdesk.conversations.show', $this->conversation),
            'tag' => 'helpdesk-conversation-'.$this->conversation->id,
        ];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_conversation_assigned',
            'title' => 'Conversacion asignada',
            'message' => "Se te ha asignado la conversacion #{$this->conversation->id}: {$this->conversation->subject}",
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.show', $this->conversation),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
