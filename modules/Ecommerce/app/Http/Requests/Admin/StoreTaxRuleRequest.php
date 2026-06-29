<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.taxes.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'basis' => ['required', 'string', 'in:price,weight'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'basis.required' => 'La base de calculo es obligatoria.',
            'basis.in' => 'La base debe ser precio o peso.',
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.min' => 'El porcentaje no puede ser negativo.',
            'percentage.max' => 'El porcentaje no puede superar 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'basis' => 'base de calculo',
            'percentage' => 'porcentaje',
            'price_from' => 'precio desde',
            'price_to' => 'precio hasta',
            'order' => 'orden',
        ];
    }
}
