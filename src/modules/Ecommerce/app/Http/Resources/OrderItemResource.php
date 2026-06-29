<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_image' => $this->product_image,
            'qty' => $this->qty,
            'price' => $this->price,
            'total' => $this->total,
            'options' => $this->options,
        ];
    }
}
