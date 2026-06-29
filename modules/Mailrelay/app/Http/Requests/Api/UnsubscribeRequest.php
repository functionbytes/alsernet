<?php

namespace Modules\Mailrelay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UnsubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint — no authentication required
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
