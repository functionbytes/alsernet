<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class OrderResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status?->value,
            'paymentStatus' => $this->payment_status,
            'paymentMethod' => $this->payment_method,
            'subTotal' => (float) $this->sub_total,
            'taxAmount' => (float) $this->tax_amount,
            'shippingAmount' => (float) $this->shipping_amount,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'itemsCount' => $this->whenCounted('items', fn () => $this->items_count, $this->whenLoaded('items', fn () => $this->items->count())),
            'createdAt' => $this->iso($this->created_at),
            'completedAt' => $this->iso($this->completed_at),
        ];
    }
}
