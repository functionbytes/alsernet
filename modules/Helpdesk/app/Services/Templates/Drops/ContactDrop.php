<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use Modules\Helpdesk\Models\Customer;

class ContactDrop extends BaseDrop
{
    public function __construct(
        private readonly Customer $customer
    ) {}

    public function id(): int
    {
        return $this->customer->id;
    }

    public function name(): string
    {
        return $this->customer->name ?? '';
    }

    public function email(): string
    {
        return $this->customer->email ?? '';
    }

    public function phone(): string
    {
        return $this->customer->phone ?: ($this->customer->whatsapp_phone ?? '');
    }

    public function company(): string
    {
        return $this->customer->company ?? '';
    }

    public function createdAt(): ?string
    {
        return $this->formatDate($this->customer->created_at);
    }

    public function customAttributes(): array
    {
        return $this->customer->custom_attributes ?? [];
    }
}
