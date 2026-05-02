<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->external_id,
            'sku' => $this->sku,
            'title' => $this->title,
            'url' => $this->url,
            'imageUrl' => $this->image_url,
            'price' => $this->price,
            'compareAtPrice' => $this->compare_at_price,
            'currency' => $this->currency,
            'inventory' => $this->inventory,
            'status' => $this->status,
            'syncedAt' => $this->synced_at?->toIso8601String(),
        ];
    }
}
