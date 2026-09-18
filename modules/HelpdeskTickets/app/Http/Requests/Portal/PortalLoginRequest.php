<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class PortalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El correo electronico debe ser una direccion valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electronico',
        ];
    }
}
