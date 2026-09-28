<?php

namespace Modules\Helpdesk\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboxItemChanged implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly string $changeType,
    ) {}

    public function broadcastOn(): array
    {
        // 'user.{id}' (routes/channels.php) — ya autorizado y ya lo escucha
        // el frontend general del panel (módulo Notification). Antes era
        // 'helpdesk.user.{id}', un canal sin autorizador que nadie
        // suscribía nunca — este evento no le llegaba a nadie.
        return [
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inbox.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'change_type' => $this->changeType,
        ];
    }
}
