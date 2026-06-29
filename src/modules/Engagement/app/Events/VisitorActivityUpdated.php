<?php

namespace Modules\Engagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorActivityUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $inboxId,
        public readonly array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('engagement.visitors'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'visitor.activity';
    }
}
