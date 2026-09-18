<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginWebRequest extends FormRequest
{
    // Public endpoint — no auth required
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo o identificación es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo o identificación',
            'password' => 'contraseña',
        ];
    }
}
