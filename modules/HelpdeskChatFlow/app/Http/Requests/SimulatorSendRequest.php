<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulatorSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chatflow.update');
    }

    public function rules(): array
    {
        return [
            'session_key' => ['required', 'string'],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_key.required' => 'Falta la sesión de prueba.',
            'message.required' => 'Escribe un mensaje.',
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'session_key' => 'sesión',
            'message' => 'mensaje',
        ];
    }
}
