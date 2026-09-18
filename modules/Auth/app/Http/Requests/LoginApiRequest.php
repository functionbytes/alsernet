<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'device_name.required' => 'El nombre del dispositivo es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo',
            'password' => 'contraseña',
            'device_name' => 'nombre del dispositivo',
        ];
    }
}
