<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsOrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function customerId(): ?int
    {
        return isset($this->payload['customer_id']) ? (int) $this->payload['customer_id'] : null;
    }

    public function orderId(): ?int
    {
        return isset($this->payload['order_id']) ? (int) $this->payload['order_id'] : null;
    }

    public function oldStatus(): ?string
    {
        return isset($this->payload['old_status']) ? (string) $this->payload['old_status'] : null;
    }

    public function newStatus(): ?string
    {
        return isset($this->payload['new_status']) ? (string) $this->payload['new_status'] : null;
    }
}
