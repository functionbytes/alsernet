<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'body' => ['nullable', 'string'],
            'template_id' => ['nullable', 'integer', 'exists:mailer_templates,id'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['email'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            if (! $this->filled('body') && ! $this->filled('template_id')) {
                $v->errors()->add('body', 'El cuerpo del email es obligatorio cuando no se selecciona una plantilla.');
            }
        });
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
