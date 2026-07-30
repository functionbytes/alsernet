<?php

namespace Modules\Helpdesk\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class InjectSimulatorMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Per-conversation authorization (token match) is enforced in the
        // controller; the public flag gates the whole feature.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'token' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Escribe un mensaje.',
            'message.max' => 'El mensaje no puede superar los 2000 caracteres.',
            'token.required' => 'Falta el token de la sesión simulada.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => 'mensaje',
            'token' => 'token',
        ];
    }
}
