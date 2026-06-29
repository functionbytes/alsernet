<?php

namespace Modules\Engagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderTrackingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionToken,
        public readonly int $orderId,
        public readonly ?string $oldStatus,
        public readonly ?string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('engagement.session.'.$this->sessionToken),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.tracking.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
