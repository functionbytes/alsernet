<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Cart;

use App\Http\Api\V1\BaseApiRequest;

class AddCartItemRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'options' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto no existe.',
            'qty.required' => 'La cantidad es obligatoria.',
            'qty.min' => 'La cantidad mínima es 1.',
        ];
    }
}
