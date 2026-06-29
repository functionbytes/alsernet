<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'order' => $this->order,
            'group_id' => $this->group_id,
            'created_at' => $this->created_at,
        ];
    }
}
