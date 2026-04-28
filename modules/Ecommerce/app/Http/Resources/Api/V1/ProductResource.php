<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;
use Modules\Ecommerce\Supports\ProductImageHelper;

class ProductResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => method_exists($this->resource, 'translate') ? $this->resource->translate('name') : $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'salePrice' => $this->sale_price ? (float) $this->sale_price : null,
            'finalPrice' => (float) $this->final_price,
            'isOnSale' => (bool) $this->is_on_sale,
            'featuredImage' => ProductImageHelper::url($this->featured_image, 'medium'),
            'stockStatus' => $this->stock_status,
            'isFeatured' => (bool) $this->is_featured,
            'status' => $this->status?->value,
            'reviewsCount' => $this->whenCounted('reviews'),
            'brand' => $this->whenIncluded('brand', fn () => new BrandResource($this->brand)),
            'categories' => $this->whenIncluded('categories', fn () => CategoryResource::collection($this->categories)),
            'createdAt' => $this->iso($this->created_at),
        ];
    }
}
