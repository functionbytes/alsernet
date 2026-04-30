<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update')
            ?? $this->user()?->can('manager.helpdesk.conversations.update')
            ?? true;
    }

    public function rules(): array
    {
        // Updates parciales: cualquier campo puede llegar solo (status, priority, assignee, etc.)
        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'status_id' => ['sometimes', 'exists:helpdesk_conversation_statuses,id'],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'is_archived' => ['sometimes', 'boolean'],
            'group_id' => ['sometimes', 'nullable', 'integer', 'exists:helpdesk.helpdesk_groups,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:helpdesk.helpdesk_conversation_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'status_id.exists' => 'El estado seleccionado no existe.',
            'assignee_id.exists' => 'El agente asignado no existe.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
            'group_id.exists' => 'El equipo seleccionado no existe.',
            'tags.*.exists' => 'Una de las etiquetas seleccionadas no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'status_id' => 'estado',
            'assignee_id' => 'agente asignado',
            'priority' => 'prioridad',
            'is_archived' => 'archivado',
            'group_id' => 'equipo',
        ];
    }
}
