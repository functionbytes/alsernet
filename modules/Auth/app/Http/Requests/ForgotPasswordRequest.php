<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    // Public endpoint — no auth required
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo o identificación es obligatorio.',
            'email.max' => 'El campo no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo o identificación',
        ];
    }
}
