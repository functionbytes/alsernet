<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class FilterResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'brands' => $this->resource['brands'] ?? [],
            'categories' => $this->resource['categories'] ?? [],
            'priceRange' => $this->resource['priceRange'] ?? ['min' => 0, 'max' => 0],
        ];
    }
}
