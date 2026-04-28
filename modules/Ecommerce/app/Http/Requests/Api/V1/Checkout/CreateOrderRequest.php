<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Checkout;

use App\Http\Api\V1\BaseApiRequest;

class CreateOrderRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer', 'exists:ecommerce_customer_addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:ecommerce_customer_addresses,id'],
            'shipping_method' => ['required', 'string', 'max:60'],
            'payment_method' => ['required', 'string', 'max:60'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'La dirección de envío es obligatoria.',
            'shipping_address_id.exists' => 'La dirección de envío no es válida.',
            'shipping_method.required' => 'El método de envío es obligatorio.',
            'payment_method.required' => 'El método de pago es obligatorio.',
        ];
    }
}
