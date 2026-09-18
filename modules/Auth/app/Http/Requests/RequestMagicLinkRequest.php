<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestMagicLinkRequest extends FormRequest
{
    // Public endpoint — no auth required
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.max' => 'El correo no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
        ];
    }
}
