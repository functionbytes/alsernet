<?php

namespace Modules\Helpdesk\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'El mensaje no puede estar vacío.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'mensaje',
        ];
    }
}
