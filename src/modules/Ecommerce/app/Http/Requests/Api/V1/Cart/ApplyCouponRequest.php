<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Cart;

use App\Http\Api\V1\BaseApiRequest;

class ApplyCouponRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del cupón es obligatorio.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'code' => ['description' => 'Código de cupón de descuento.', 'example' => 'VERANO20'],
        ];
    }
}
