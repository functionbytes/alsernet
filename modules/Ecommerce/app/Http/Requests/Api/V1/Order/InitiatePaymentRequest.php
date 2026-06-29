<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Order;

use App\Http\Api\V1\BaseApiRequest;

class InitiatePaymentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'max:60'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => 'El canal de pago es obligatorio.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'channel' => ['description' => 'Canal de pago.', 'example' => 'wompi'],
            'return_url' => ['description' => 'URL de retorno tras el pago (para deep link).', 'example' => 'miapp://payment-result'],
        ];
    }
}
