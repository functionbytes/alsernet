<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'paymentStatus' => $this->payment_status,
            'total' => $this->total,
            'subTotal' => $this->sub_total,
            'shippingAmount' => $this->shipping_amount,
            'taxAmount' => $this->tax_amount,
            'discountAmount' => $this->discount_amount,
            'createdAt' => $this->created_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipments' => ShipmentResource::collection($this->whenLoaded('shipments')),
        ];
    }
}
