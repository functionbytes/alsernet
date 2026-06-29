<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecificationAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.specification-attributes.create');
    }

    public function rules(): array
    {
        $optionsRequired = in_array($this->input('type'), ['select', 'radio']);

        return [
            'group_id' => ['required', 'exists:ecommerce_specification_groups,id'],
            'name' => ['required', 'string', 'max:250'],
            'type' => ['required', 'in:text,textarea,select,checkbox,radio'],
            'default_value' => ['nullable', 'string', 'max:250'],
            'options' => [$optionsRequired ? 'required' : 'nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.required' => 'El grupo es obligatorio.',
            'group_id.exists' => 'El grupo seleccionado no es valido.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 250 caracteres.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser: texto, area de texto, seleccion, casilla o radio.',
            'default_value.max' => 'El valor por defecto no puede superar los 250 caracteres.',
            'options.required' => 'Las opciones son obligatorias para el tipo seleccionado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'group_id' => 'grupo',
            'name' => 'nombre',
            'type' => 'tipo',
            'default_value' => 'valor por defecto',
            'options' => 'opciones',
        ];
    }
}
