<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class BrandDetailResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->mediaUrl($this->logo),
            'website' => $this->website,
            'isFeatured' => (bool) $this->is_featured,
            'productsCount' => $this->whenCounted('products'),
            'createdAt' => $this->iso($this->created_at),
        ];
    }
}
