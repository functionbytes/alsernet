<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingRuleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.shipping.update');
    }

    public function rules(): array
    {
        return [
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'adjustment_price' => ['required', 'numeric'],
            'is_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_price.required' => 'El precio de ajuste es obligatorio.',
            'adjustment_price.numeric' => 'El precio de ajuste debe ser un numero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'country' => 'pais',
            'state' => 'estado',
            'city' => 'ciudad',
            'adjustment_price' => 'precio de ajuste',
            'is_enabled' => 'habilitado',
        ];
    }
}
