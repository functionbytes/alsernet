<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class CategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => method_exists($this->resource, 'translate') ? $this->resource->translate('name') : $this->name,
            'slug' => $this->slug,
            'image' => $this->mediaUrl($this->image),
            'isFeatured' => (bool) $this->is_featured,
            'parentId' => $this->parent_id,
            'productsCount' => $this->whenCounted('products'),
            'children' => $this->whenIncluded('children', fn () => CategoryResource::collection($this->children)),
        ];
    }
}
