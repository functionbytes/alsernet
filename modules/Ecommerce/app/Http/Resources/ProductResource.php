<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'final_price' => $this->final_price,
            'is_on_sale' => $this->is_on_sale,
            'sku' => $this->sku,
            'stock_status' => $this->stock_status,
            'quantity' => $this->quantity,
            'images' => $this->images,
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'categories' => ProductCategoryResource::collection($this->whenLoaded('categories')),
            'tags' => ProductTagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
