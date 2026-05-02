<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'imageUrl' => $this->image_url,
            'productId' => $this->product_id,
        ];
    }
}
