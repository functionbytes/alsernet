<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'optionType' => $this->option_type,
            'required' => $this->required,
            'order' => $this->order,
            'values' => ProductOptionValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
