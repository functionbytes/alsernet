<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class BrandResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => method_exists($this->resource, 'translate') ? $this->resource->translate('name') : $this->name,
            'slug' => $this->slug,
            'logo' => $this->mediaUrl($this->logo),
            'isFeatured' => (bool) $this->is_featured,
            'productsCount' => $this->whenCounted('products'),
        ];
    }
}
