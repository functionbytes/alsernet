<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'min_order_price' => $this->min_order_price,
            'quantity' => $this->quantity,
            'total_used' => $this->total_used,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
        ];
    }
}
