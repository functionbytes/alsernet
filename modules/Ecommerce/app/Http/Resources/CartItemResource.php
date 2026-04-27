<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $price = $this->product?->final_price ?? 0;

        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'name' => $this->product?->name,
            'qty' => $this->qty,
            'price' => $price,
            'total' => $this->qty * $price,
            'image' => $this->product?->featured_image,
        ];
    }
}
