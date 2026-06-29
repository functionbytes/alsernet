<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
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
            'likert_rows' => ['nullable', 'array'],
            'show_char_counter' => ['boolean'],
            'label_position' => ['nullable', 'in:top,floating,hidden'],
            'css_class' => ['nullable', 'string', 'max:255'],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['nullable', 'array'],
            'translations.*.label' => ['nullable', 'string', 'max:255'],
            'translations.*.placeholder' => ['nullable', 'string', 'max:255'],
            'translations.*.consent_text' => ['nullable', 'string'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string', 'in:equals,not_equals,contains,not_empty,empty,greater_than,less_than'],
            'conditions.*.value' => ['nullable', 'string'],
            'conditions.*.logic' => ['nullable', 'string', 'in:OR'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'conditions.*.operator.in' => 'El operador de la condición no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'etiqueta',
            'placeholder' => 'placeholder',
            'default_value' => 'valor por defecto',
            'options' => 'opciones',
            'is_required' => 'obligatorio',
        ];
    }
}
