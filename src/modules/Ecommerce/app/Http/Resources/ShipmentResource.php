<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'shipment_id' => $this->shipment_id,
            'weight' => $this->weight,
            'price' => $this->price,
            'status' => $this->status,
            'note' => $this->note,
            'order' => new OrderResource($this->whenLoaded('order')),
            'created_at' => $this->created_at,
        ];
    }
}
