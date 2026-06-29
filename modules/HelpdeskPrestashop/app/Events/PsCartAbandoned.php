<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsCartAbandoned
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function customerId(): ?int
    {
        return isset($this->payload['customer_id']) ? (int) $this->payload['customer_id'] : null;
    }

    public function cartId(): ?int
    {
        return isset($this->payload['cart_id']) ? (int) $this->payload['cart_id'] : null;
    }

    public function email(): ?string
    {
        return isset($this->payload['email']) ? (string) $this->payload['email'] : null;
    }

    /** @return array<int, mixed> */
    public function items(): array
    {
        return is_array($this->payload['items'] ?? null) ? $this->payload['items'] : [];
    }

    public function total(): float
    {
        return isset($this->payload['total']) ? (float) $this->payload['total'] : 0.0;
    }

    public function abandonedAt(): ?string
    {
        return isset($this->payload['abandoned_at']) ? (string) $this->payload['abandoned_at'] : null;
    }
}
