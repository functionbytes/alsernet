<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsBackInStock
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function productId(): ?int
    {
        return isset($this->payload['product_id']) ? (int) $this->payload['product_id'] : null;
    }

    /** @return array<string, mixed> */
    public function productData(): array
    {
        return is_array($this->payload['product_data'] ?? null) ? $this->payload['product_data'] : [];
    }
}
