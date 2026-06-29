<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class OrderDetailResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'token' => $this->token,
            'status' => $this->status?->value,
            'paymentStatus' => $this->payment_status,
            'paymentMethod' => $this->payment_method,
            'transactionId' => $this->transaction_id,
            'subTotal' => (float) $this->sub_total,
            'taxAmount' => (float) $this->tax_amount,
            'shippingAmount' => (float) $this->shipping_amount,
            'discountAmount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'shippingMethod' => $this->shipping_method,
            'couponCode' => $this->coupon_code,
            'customerNote' => $this->customer_note,
            'deliveryDate' => $this->iso($this->delivery_date),
            'deliveryTime' => $this->delivery_time,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'productId' => $i->product_id,
                'productName' => $i->product_name,
                'sku' => $i->sku,
                'qty' => (int) $i->qty,
                'price' => (float) $i->price,
                'total' => (float) ($i->price * $i->qty),
            ])),
            'addresses' => $this->whenLoaded('addresses', fn () => $this->addresses->map(fn ($a) => [
                'type' => $a->type,
                'name' => $a->name,
                'phone' => $a->phone,
                'email' => $a->email,
                'country' => $a->country,
                'state' => $a->state,
                'city' => $a->city,
                'address' => $a->address,
            ])),
            'createdAt' => $this->iso($this->created_at),
            'updatedAt' => $this->iso($this->updated_at),
            'completedAt' => $this->iso($this->completed_at),
        ];
    }
}
