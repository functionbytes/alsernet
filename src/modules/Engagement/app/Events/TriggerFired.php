<?php

namespace Modules\Engagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TriggerFired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionToken,
        public readonly int $ruleId,
        public readonly array $action,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('widget-session.'.$this->sessionToken),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TriggerFired';
    }

    public function broadcastWith(): array
    {
        return [
            'sessionToken' => $this->sessionToken,
            'ruleId' => $this->ruleId,
            'action' => $this->action,
        ];
    }
}
