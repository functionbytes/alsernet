<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.shipping.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:based_on_price,based_on_weight,based_on_zipcode,based_on_location'],
            'from' => ['nullable', 'numeric', 'min:0'],
            'to' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'Tipo de regla invalido.',
            'price.required' => 'El precio es obligatorio.',
            'price.min' => 'El precio no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'from' => 'desde',
            'to' => 'hasta',
            'price' => 'precio',
        ];
    }
}
