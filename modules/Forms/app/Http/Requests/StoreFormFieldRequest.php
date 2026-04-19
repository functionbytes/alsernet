<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        $fieldTypes = array_keys(config('forms.field_types', []));

        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', $fieldTypes)],
            'key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'validation_rules' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'width' => ['nullable', 'in:full,half,third,quarter'],
            'step_number' => ['nullable', 'integer', 'min:1'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'step_value' => ['nullable', 'numeric'],
            'html_content' => ['nullable', 'string'],
            'consent_text' => ['nullable', 'string'],
            'formula' => ['nullable', 'string', 'max:500'],
            'mailrelay_group_id' => ['nullable', 'integer'],
            'auto_populate_param' => ['nullable', 'string', 'max:100'],
            'likert_rows' => ['nullable', 'array'],
            'show_char_counter' => ['boolean'],
            'label_position' => ['nullable', 'in:top,floating,hidden'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'type.required' => 'El tipo de campo es obligatorio.',
            'type.in' => 'El tipo de campo seleccionado no es válido.',
            'key.regex' => 'La clave sólo puede contener letras minúsculas, números y guiones bajos.',
            'key.max' => 'La clave no puede superar los 100 caracteres.',
            'placeholder.max' => 'El placeholder no puede superar los 255 caracteres.',
            'help_text.max' => 'El texto de ayuda no puede superar los 500 caracteres.',
            'width.in' => 'El ancho seleccionado no es válido.',
            'step_number.min' => 'El número de paso debe ser al menos 1.',
            'options.*.value.required_with' => 'Cada opción debe tener un valor.',
            'options.*.label.required_with' => 'Cada opción debe tener una etiqueta.',
            'formula.max' => 'La fórmula no puede superar los 500 caracteres.',
            'label_position.in' => 'La posición de la etiqueta no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'etiqueta',
            'type' => 'tipo de campo',
            'key' => 'clave',
            'placeholder' => 'placeholder',
            'default_value' => 'valor por defecto',
            'options' => 'opciones',
            'validation_rules' => 'reglas de validación',
            'is_required' => 'obligatorio',
            'width' => 'ancho',
            'step_number' => 'número de paso',
            'help_text' => 'texto de ayuda',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'step_value' => 'incremento',
            'formula' => 'fórmula',
            'auto_populate_param' => 'parámetro de autorelleno',
            'label_position' => 'posición de etiqueta',
        ];
    }
}
