<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCustomEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('submission')) ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:50000'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.required' => 'El destinatario es obligatorio.',
            'recipient.email' => 'El destinatario debe ser un correo electrónico válido.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 50000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient' => 'destinatario',
            'subject' => 'asunto',
            'message' => 'mensaje',
        ];
    }
}
