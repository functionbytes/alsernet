<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneralSettingRequest extends FormRequest
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
            'setting_key' => [
                'required',
                'string',
                'max:255',
                'unique:mailing_general_settings,setting_key',
            ],
            'setting_value' => [
                'required',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
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
            'setting_key.required' => 'La clave de la configuración es obligatoria.',
            'setting_key.string' => 'La clave debe ser una cadena de texto.',
            'setting_key.max' => 'La clave no debe exceder 255 caracteres.',
            'setting_key.unique' => 'Esta clave de configuración ya existe.',
            'setting_value.required' => 'El valor de la configuración es obligatorio.',
            'setting_value.string' => 'El valor debe ser una cadena de texto.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no debe exceder 1000 caracteres.',
        ];
    }
}
