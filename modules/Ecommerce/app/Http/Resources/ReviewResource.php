<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'star' => $this->star,
            'comment' => $this->comment,
            'status' => $this->status,
            'images' => $this->images,
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
        ];
    }
}
