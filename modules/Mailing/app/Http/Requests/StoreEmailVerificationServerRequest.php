<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailVerificationServerRequest extends FormRequest
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
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                Rule::in(['zerobounce', 'neverbounce', 'kickbox', 'clearout', 'debounce', 'custom']),
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'error']),
            ],
            'api_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'api_options' => [
                'nullable',
                'array',
            ],
            'check_disposable' => [
                'nullable',
                'boolean',
            ],
            'check_role_based' => [
                'nullable',
                'boolean',
            ],
            'check_smtp' => [
                'nullable',
                'boolean',
            ],
            'check_catch_all' => [
                'nullable',
                'boolean',
            ],
            'requests_per_minute' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
            'requests_per_day' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
            'credit_threshold' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ];

        // All types require API key (except custom which may not)
        if ($this->input('type') !== 'custom') {
            $rules['api_key'] = [
                'required',
                'string',
                'min:10',
            ];
        } else {
            $rules['api_key'] = [
                'nullable',
                'string',
                'min:10',
            ];
        }

        // Type-specific validations
        if ($this->input('type') === 'zerobounce') {
            $rules['api_options.mode'] = [
                'nullable',
                Rule::in(['production', 'sandbox']),
            ];
        }

        if ($this->input('type') === 'neverbounce') {
            $rules['api_options.token_timeout'] = [
                'nullable',
                'integer',
                'min:300',
            ];
        }

        if ($this->input('type') === 'custom') {
            $rules = array_merge($rules, [
                'api_url' => [
                    'required',
                    'url',
                    'max:500',
                ],
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del servidor es obligatorio.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'type.required' => 'Debe seleccionar un tipo de servidor.',
            'type.in' => 'El tipo de servidor seleccionado no es válido.',
            'api_key.required' => 'La clave API es obligatoria.',
            'api_key.min' => 'La clave API debe tener al menos 10 caracteres.',
            'api_url.url' => 'La URL de la API debe ser válida.',
            'api_url.max' => 'La URL de la API no debe exceder 500 caracteres.',
            'api_url.required' => 'La URL de la API es obligatoria para servidores personalizados.',
            'status.in' => 'El estado debe ser activo, inactivo o error.',
            'api_options.array' => 'Las opciones de API deben ser un arreglo.',
            'api_options.mode' => 'El modo debe ser "production" o "sandbox".',
            'api_options.token_timeout' => 'El tiempo de espera del token debe ser al menos 300 segundos.',
            'requests_per_minute.min' => 'Las solicitudes por minuto deben ser al menos 1.',
            'requests_per_minute.max' => 'Las solicitudes por minuto no pueden exceder 1000.',
            'requests_per_day.min' => 'Las solicitudes por día deben ser al menos 1.',
            'requests_per_day.max' => 'Las solicitudes por día no pueden exceder 1.000.000.',
            'credit_threshold.min' => 'El umbral de crédito no puede ser menor a 0.',
            'credit_threshold.max' => 'El umbral de crédito no puede exceder 100.',
            'check_disposable.boolean' => 'La opción de verificar desechables debe ser booleana.',
            'check_role_based.boolean' => 'La opción de verificar basada en roles debe ser booleana.',
            'check_smtp.boolean' => 'La opción de verificación SMTP debe ser booleana.',
            'check_catch_all.boolean' => 'La opción de capturar todo debe ser booleana.',
        ];
    }
}
