<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:archive,unarchive,close,reopen,assign,tag,mark_read,mark_unread,priority'],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:helpdesk.helpdesk_conversations,id'],
            'payload' => ['nullable', 'array'],
            'payload.assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'payload.tag_ids' => ['nullable', 'array'],
            'payload.tag_ids.*' => ['integer', 'exists:helpdesk.helpdesk_conversation_tags,id'],
            'payload.priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'ids.required' => 'Debes seleccionar al menos una conversación.',
            'ids.min' => 'Debes seleccionar al menos una conversación.',
            'ids.max' => 'No puedes seleccionar más de 100 conversaciones a la vez.',
            'ids.*.exists' => 'Una o más conversaciones seleccionadas no existen.',
            'payload.assignee_id.exists' => 'El agente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'conversaciones',
            'payload.assignee_id' => 'agente',
            'payload.tag_ids' => 'etiquetas',
        ];
    }
}
