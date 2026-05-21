<?php

namespace Modules\HelpdeskErp\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ErpOrdersReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly ?int $customerId = null
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('erp-orders-ready.'.md5(strtolower(trim($this->email))))];
    }

    public function broadcastAs(): string
    {
        return 'erp.orders.ready';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'email_hash' => md5(strtolower(trim($this->email))),
            'customer_id' => $this->customerId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
