<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class StoreConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        $hasAttachments = $this->hasFile('attachments');
        $settings = app(HelpdeskSettings::class);

        return [
            'body' => [$hasAttachments ? 'nullable' : 'required', 'nullable', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            // El ítem citado DEBE pertenecer a esta conversación: sin este scope
            // un agente podría citar un ítem de otra conversación/inbox (o una nota
            // interna ajena) y filtrar su autor + cuerpo (IDOR cross-inbox).
            'reply_to_id' => [
                'nullable',
                'integer',
                Rule::exists('helpdesk.helpdesk_conversation_items', 'id')
                    ->where('conversation_id', $this->route('conversation')?->id),
            ],
            'attachments' => [
                $hasAttachments ? 'required' : 'nullable',
                'array',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            'attachments.*' => [
                'nullable',
                'file',
                'max:'.$settings->attachmentMaxKilobytes(),
                'mimes:'.implode(',', $settings->attachmentExtensions()),
                new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
            ],
            'action' => ['nullable', 'in:send,send_and_close'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'El cuerpo del mensaje es obligatorio.',
            'attachments.*.max' => 'Cada archivo no puede superar el límite configurado en Helpdesk.',
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
