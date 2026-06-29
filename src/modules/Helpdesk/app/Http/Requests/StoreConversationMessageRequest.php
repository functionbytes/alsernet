<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        $hasAttachments = $this->hasFile('attachments');

        return [
            'body' => [$hasAttachments ? 'nullable' : 'required', 'nullable', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'reply_to_id' => ['nullable', 'integer'],
            'attachments' => [$hasAttachments ? 'required' : 'nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,zip,mp4,mp3,ogg'],
            'action' => ['nullable', 'in:send,send_and_close'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El cuerpo del mensaje es obligatorio.',
            'attachments.*.max' => 'Cada archivo no puede superar los 10 MB.',
            'attachments.*.mimes' => 'Tipo de archivo no permitido.',
            'action.in' => 'La acción debe ser: enviar o enviar y cerrar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'mensaje',
            'is_internal' => 'nota interna',
            'action' => 'acción',
        ];
    }
}
