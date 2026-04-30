<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailFromConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()?->can('helpdesk.conversations.update')
            && $this->user()?->can('update', $conversation) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['email'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto del email es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'body.required' => 'El cuerpo del email es obligatorio.',
            'cc.*.email' => 'Cada dirección en copia debe ser un email válido.',
            'bcc.*.email' => 'Cada dirección en copia oculta debe ser un email válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'body' => 'cuerpo del mensaje',
            'cc' => 'copia',
            'bcc' => 'copia oculta',
        ];
    }
}
