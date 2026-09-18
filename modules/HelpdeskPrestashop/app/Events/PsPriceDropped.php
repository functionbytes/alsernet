<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsPriceDropped
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function productId(): ?int
    {
        return isset($this->payload['product_id']) ? (int) $this->payload['product_id'] : null;
    }

    public function oldPrice(): float
    {
        return isset($this->payload['old_price']) ? (float) $this->payload['old_price'] : 0.0;
    }

    public function newPrice(): float
    {
        return isset($this->payload['new_price']) ? (float) $this->payload['new_price'] : 0.0;
    }

    public function dropPercent(): float
    {
        return isset($this->payload['drop_percent']) ? (float) $this->payload['drop_percent'] : 0.0;
    }

    /** @return array<string, mixed> */
    public function productData(): array
    {
        return is_array($this->payload['product_data'] ?? null) ? $this->payload['product_data'] : [];
    }
}
