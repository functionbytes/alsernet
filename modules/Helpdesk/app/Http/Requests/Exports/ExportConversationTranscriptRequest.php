<?php

namespace Modules\Helpdesk\Http\Requests\Exports;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Conversation;

class ExportConversationTranscriptRequest extends FormRequest
{
    /**
     * The route has no {conversation} binding — the id travels as a query
     * field (conversation_id) validated below. When it resolves to a real
     * conversation we replicate ConversationPolicy::view() here (including its
     * assignee_id fallback) so access stays identical to the previous
     * inline-authorize() flow. An invalid/missing id is allowed through so the
     * "required|integer" rule (422) or the controller's findOrFail() (404)
     * still produce the original error, instead of a misleading 403.
     */
    public function authorize(): bool
    {
        $conversation = Conversation::find($this->input('conversation_id'));

        if (! $conversation) {
            return true;
        }

        return (bool) $this->user()?->can('view', $conversation);
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'integer'],
            'format' => ['required', 'in:pdf,csv,json,eml'],
            'include_notes' => ['nullable', 'boolean'],
            'include_meta' => ['nullable', 'boolean'],
            'include_attachments' => ['nullable', 'boolean'],
            'include_header' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'La conversación es obligatoria.',
            'conversation_id.integer' => 'La conversación indicada no es válida.',
            'format.required' => 'El formato de exportación es obligatorio.',
            'format.in' => 'El formato debe ser pdf, csv, json o eml.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_id' => 'conversación',
            'format' => 'formato',
            'include_notes' => 'incluir notas internas',
            'include_meta' => 'incluir metadatos',
            'include_attachments' => 'incluir adjuntos',
            'include_header' => 'incluir encabezado',
        ];
    }
}
