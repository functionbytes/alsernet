<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class AdminOrderReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'code' => $this->order->code,
            'total' => $this->order->total,
            'customer_name' => $this->order->customer?->name ?? 'Invitado',
            'created_at' => $this->order->created_at?->toIso8601String(),
        ];
    }
}
