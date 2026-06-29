<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_address' => $this->customer_address,
            'sub_total' => $this->sub_total,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'discount_amount' => $this->discount_amount,
            'amount' => $this->amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
