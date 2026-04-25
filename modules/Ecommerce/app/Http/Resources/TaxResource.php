<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'percentage' => $this->percentage,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
