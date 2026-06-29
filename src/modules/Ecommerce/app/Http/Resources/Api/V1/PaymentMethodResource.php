<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class PaymentMethodResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $gateway = $this->resource;
        $subTotal = (float) ($request->input('subtotal') ?? 0);

        return [
            'channel' => $gateway->getChannel(),
            'name' => $gateway->getName(),
            'description' => $gateway->getDescription(),
            'fee' => $gateway->getFee($subTotal),
        ];
    }
}
