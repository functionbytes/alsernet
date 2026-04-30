<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CalculateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El codigo de descuento es obligatorio.',
            'code.string' => 'El codigo de descuento debe ser texto.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.numeric' => 'El subtotal debe ser un numero.',
            'subtotal.min' => 'El subtotal no puede ser negativo.',
        ];
    }
}
