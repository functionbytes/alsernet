<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.product-options.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'option_type' => ['required', 'string', 'in:text,select,radio,checkbox,color,date'],
            'required' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'values' => ['nullable', 'array'],
            'values.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'option_type.required' => 'El tipo de opcion es obligatorio.',
            'option_type.in' => 'Tipo de opcion invalido.',
            'values.*.required' => 'El valor no puede estar vacio.',
            'values.*.max' => 'El valor no puede superar los 255 caracteres.',
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
        ];
    }
}
