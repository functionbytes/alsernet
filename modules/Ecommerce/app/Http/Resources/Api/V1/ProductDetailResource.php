<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;
use Modules\Ecommerce\Supports\ProductImageHelper;

class ProductDetailResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $translatable = fn (string $field) => method_exists($this->resource, 'translate')
            ? $this->resource->translate($field)
            : $this->{$field};

        return [
            'id' => $this->id,
            'name' => $translatable('name'),
            'slug' => $this->slug,
            'description' => $translatable('description'),
            'content' => $translatable('content'),
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'salePrice' => $this->sale_price ? (float) $this->sale_price : null,
            'finalPrice' => (float) $this->final_price,
            'isOnSale' => (bool) $this->is_on_sale,
            'featuredImage' => ProductImageHelper::url($this->featured_image, 'large'),
            'images' => collect($this->images ?? [])->map(fn ($path) => ProductImageHelper::url($path, 'large'))->values(),
            'stockStatus' => $this->stock_status,
            'quantity' => $this->quantity,
            'isFeatured' => (bool) $this->is_featured,
            'status' => $this->status?->value,
            'weight' => $this->weight ? (float) $this->weight : null,
            'length' => $this->length ? (float) $this->length : null,
            'wide' => $this->wide ? (float) $this->wide : null,
            'height' => $this->height ? (float) $this->height : null,
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'reviewsCount' => $this->whenCounted('reviews'),
            'averageRating' => $this->reviews_avg_star ? round((float) $this->reviews_avg_star, 1) : null,
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($this->brand)),
            'categories' => $this->whenLoaded('categories', fn () => CategoryResource::collection($this->categories)),
            'reviews' => $this->whenLoaded('reviews', fn () => ReviewResource::collection($this->reviews)),
            'createdAt' => $this->iso($this->created_at),
            'updatedAt' => $this->iso($this->updated_at),
        ];
    }
}
