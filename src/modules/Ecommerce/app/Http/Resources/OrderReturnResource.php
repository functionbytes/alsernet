<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reason' => $this->reason,
            'order_status' => $this->order_status,
            'return_status' => $this->return_status,
            'order' => new OrderResource($this->whenLoaded('order')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at,
        ];
    }
}
