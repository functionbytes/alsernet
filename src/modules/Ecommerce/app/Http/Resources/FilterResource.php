<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'priceMin' => $this->resource['price_min'] ?? null,
            'priceMax' => $this->resource['price_max'] ?? null,
            'brands' => collect($this->resource['brands'] ?? [])->map(fn ($b) => [
                'id' => $b['id'],
                'name' => $b['name'],
            ])->values(),
            'categories' => collect($this->resource['categories'] ?? [])->map(fn ($c) => [
                'id' => $c['id'],
                'name' => $c['name'],
                'count' => $c['count'] ?? 0,
            ])->values(),
        ];
    }
}
