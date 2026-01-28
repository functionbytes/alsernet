<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLanguageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                Rule::unique('mailing_languages', 'language_code'),
            ],
            'language_name' => [
                'required',
                'string',
                'max:255',
            ],
            'is_default' => [
                'boolean',
            ],
            'translations' => [
                'nullable',
                'array',
            ],
            'translations.*' => [
                'string',
                'max:1000',
            ],
            'settings' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'language_code.required' => 'El código de idioma es obligatorio.',
            'language_code.size' => 'El código de idioma debe contener exactamente 2 caracteres (ISO 639-1).',
            'language_code.regex' => 'El código de idioma debe ser válido según ISO 639-1 (ej: en, es, fr).',
            'language_code.unique' => 'Este código de idioma ya existe en el sistema.',
            'language_name.required' => 'El nombre del idioma es obligatorio.',
            'language_name.max' => 'El nombre del idioma no debe exceder 255 caracteres.',
            'is_default.boolean' => 'El campo predeterminado debe ser verdadero o falso.',
            'translations.array' => 'Las traducciones deben ser un arreglo.',
            'translations.*.string' => 'Cada traducción debe ser texto.',
            'translations.*.max' => 'Cada traducción no debe exceder 1000 caracteres.',
            'settings.array' => 'Las configuraciones deben ser un arreglo.',
        ];
    }
}
