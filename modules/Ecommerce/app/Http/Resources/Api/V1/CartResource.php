<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class CartResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $items = $this->resource;

        $subTotal = $items->sum(fn ($i) => (float) ($i->product->price ?? 0) * (int) $i->qty);

        return [
            'items' => CartItemResource::collection($items)->toArray($request),
            'totals' => [
                'subTotal' => $subTotal,
                'itemsCount' => $items->sum('qty'),
            ],
        ];
    }
}
