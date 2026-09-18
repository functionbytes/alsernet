<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackAbandonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'max:255'],
            'partial_data' => ['nullable', 'json'],
            'current_step' => ['nullable', 'integer', 'min:1'],
            'last_field_key' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_token.required' => 'Falta el token de sesión.',
            'partial_data.json' => 'Los datos parciales deben ser JSON válido.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }
}
