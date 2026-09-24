<?php

namespace Modules\HelpdeskPrestashop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PsCustomerCreated
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

    public function firstname(): ?string
    {
        return isset($this->payload['firstname']) ? (string) $this->payload['firstname'] : null;
    }

    public function lastname(): ?string
    {
        return isset($this->payload['lastname']) ? (string) $this->payload['lastname'] : null;
    }
}
