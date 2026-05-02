<?php

namespace Modules\Remarketing\DTOs;

use Carbon\Carbon;

class EventDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $externalId,
        public readonly int $storeId,
        public readonly array $properties,
        public readonly Carbon $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'external_id' => $this->externalId,
            'store_id' => $this->storeId,
            'properties' => $this->properties,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
