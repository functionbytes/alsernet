<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class CategoryDetailResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->mediaUrl($this->image),
            'isFeatured' => (bool) $this->is_featured,
            'parentId' => $this->parent_id,
            'parent' => $this->whenIncluded('parent', fn () => new CategoryResource($this->parent)),
            'children' => $this->whenIncluded('children', fn () => CategoryResource::collection($this->children)),
            'productsCount' => $this->whenCounted('products'),
        ];
    }
}
