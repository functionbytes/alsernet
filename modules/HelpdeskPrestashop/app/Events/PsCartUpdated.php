<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsCartUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function customerId(): ?int
    {
        return isset($this->payload['customer_id']) ? (int) $this->payload['customer_id'] : null;
    }

    public function email(): ?string
    {
        return isset($this->payload['email']) ? (string) $this->payload['email'] : null;
    }

    public function cartId(): ?int
    {
        return isset($this->payload['cart_id']) ? (int) $this->payload['cart_id'] : null;
    }
}
