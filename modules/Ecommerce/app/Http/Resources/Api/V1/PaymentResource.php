<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class PaymentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order_id,
            'channel' => $this->payment_channel ?? $this->channel ?? null,
            'amount' => (float) ($this->amount ?? 0),
            'currency' => $this->currency ?? null,
            'status' => $this->status,
            'transactionId' => $this->transaction_id ?? null,
            'createdAt' => $this->iso($this->created_at),
        ];
    }
}
