<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderShipmentRequest extends FormRequest
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
            'note' => ['nullable', 'string', 'max:500'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.string' => 'La nota debe ser texto.',
            'note.max' => 'La nota no puede tener mas de :max caracteres.',
            'weight.numeric' => 'El peso debe ser un numero.',
            'weight.min' => 'El peso no puede ser negativo.',
        ];
    }
}
