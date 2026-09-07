<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormFieldEditorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'is_required' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'placeholder.max' => 'El placeholder no puede superar los 255 caracteres.',
            'help_text.max' => 'El texto de ayuda no puede superar los 500 caracteres.',
            'is_required' => 'El campo requerido debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'etiqueta',
            'placeholder' => 'placeholder',
            'help_text' => 'texto de ayuda',
            'is_required' => 'campo requerido',
        ];
    }
}
