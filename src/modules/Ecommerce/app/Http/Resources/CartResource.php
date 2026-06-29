<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'qty' => $this->qty,
            'price' => $this->product?->final_price,
            'total' => $this->qty * ($this->product?->final_price ?? 0),
        ];
    }
}
