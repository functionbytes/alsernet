<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'color' => $this->color,
            'image' => $this->image,
            'is_default' => $this->is_default,
            'order' => $this->order,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
