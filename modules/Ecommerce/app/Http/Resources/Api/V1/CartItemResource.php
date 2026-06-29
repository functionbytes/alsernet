<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class CartItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'qty' => (int) $this->qty,
            'options' => $this->options,
            'product' => $this->whenLoaded('product', fn () => (new ProductResource($this->product))->toArray($request)),
            'createdAt' => $this->iso($this->created_at),
            'updatedAt' => $this->iso($this->updated_at),
        ];
    }
}
