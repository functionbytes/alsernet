<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.global-options.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'option_type' => ['required', 'string', 'in:text,dropdown,checkbox,radio'],
            'required' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'values' => ['nullable', 'array'],
            'values.*.option_value' => ['required_with:values', 'string', 'max:255'],
            'values.*.affect_price' => ['nullable', 'numeric', 'min:0'],
            'values.*.affect_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'values.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'option_type.required' => 'El tipo de opcion es obligatorio.',
            'option_type.in' => 'El tipo de opcion debe ser texto, desplegable, casilla o radio.',
            'order.integer' => 'El orden debe ser un numero entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.',
            'values.*.option_value.required_with' => 'El valor de la opcion es obligatorio.',
            'values.*.option_value.max' => 'El valor no puede superar los 255 caracteres.',
            'values.*.affect_price.numeric' => 'El precio debe ser un valor numerico.',
            'values.*.affect_price.min' => 'El precio no puede ser negativo.',
            'values.*.affect_type.in' => 'El tipo de efecto debe ser fijo o porcentaje.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'option_type' => 'tipo de opcion',
            'required' => 'requerido',
            'order' => 'orden',
            'values' => 'valores',
            'values.*.option_value' => 'valor de opcion',
            'values.*.affect_price' => 'precio de efecto',
            'values.*.affect_type' => 'tipo de efecto',
            'values.*.order' => 'orden del valor',
        ];
    }
}
