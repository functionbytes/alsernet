<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'new_email' => ['required', 'email', 'max:255', 'different:email'],
            'current_password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_email.required' => 'El nuevo correo es obligatorio.',
            'new_email.email' => 'El nuevo correo no es válido.',
            'new_email.max' => 'El correo no puede superar los 255 caracteres.',
            'new_email.different' => 'El nuevo correo debe ser diferente al actual.',
            'current_password.required' => 'La contraseña actual es obligatoria.',
        ];
    }

    public function attributes(): array
    {
        return [
            'new_email' => 'nuevo correo',
            'current_password' => 'contraseña actual',
        ];
    }
}
