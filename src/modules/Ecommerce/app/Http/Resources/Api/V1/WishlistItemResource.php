<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class WishlistItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'addedAt' => $this->iso($this->created_at),
            'product' => $this->whenLoaded('product', fn () => (new ProductResource($this->product))->toArray($request)),
        ];
    }
}
