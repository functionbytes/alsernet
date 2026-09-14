<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.custom-fields.manage');
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:customer,conversation'],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:text,number,date,boolean,select,multi-select'],
            'options' => ['nullable', 'string'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.required' => 'La entidad es obligatoria.',
            'entity_type.in' => 'La entidad debe ser cliente o conversacion.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 100 caracteres.',
            'type.required' => 'El tipo de campo es obligatorio.',
            'type.in' => 'El tipo de campo seleccionado no es valido.',
            'default_value.max' => 'El valor por defecto no puede superar los 255 caracteres.',
            'order.integer' => 'El orden debe ser un numero entero.',
            'order.min' => 'El orden no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'entity_type' => 'entidad',
            'label' => 'etiqueta',
            'type' => 'tipo',
            'options' => 'opciones',
            'default_value' => 'valor por defecto',
            'is_required' => 'campo requerido',
            'order' => 'orden',
        ];
    }
}
