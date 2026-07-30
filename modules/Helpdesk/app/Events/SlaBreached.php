<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

class SlaBreached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('helpdesk.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'sla.breached';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'channel' => $this->conversation->channel,
            'created_at' => $this->conversation->created_at?->toIso8601String(),
        ];
    }
}
