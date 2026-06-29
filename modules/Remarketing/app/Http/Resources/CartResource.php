<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->external_id,
            'email' => $this->email,
            'total' => $this->total,
            'currency' => $this->currency,
            'status' => $this->status,
            'abandonedAt' => $this->abandoned_at?->toIso8601String(),
            'recoveredAt' => $this->recovered_at?->toIso8601String(),
            'url' => $this->url,
            'items' => $this->items,
        ];
    }
}
