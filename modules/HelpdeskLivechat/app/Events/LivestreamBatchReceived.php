<?php

namespace Modules\HelpdeskLivechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LivestreamBatchReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $conversationId,
        public readonly array $events,
        public readonly int $batchIndex,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("livestream.conversation.{$this->conversationId}")];
    }

    public function broadcastAs(): string
    {
        return 'livestream.batch';
    }

    public function broadcastWith(): array
    {
        return [
            'batch_index' => $this->batchIndex,
            'events' => $this->events,
            'received_at' => now()->toIso8601String(),
        ];
    }
}
