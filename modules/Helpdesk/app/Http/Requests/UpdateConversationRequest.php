<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manager.helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'status_id' => ['required', 'exists:helpdesk_conversation_statuses,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'is_archived' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'status_id.required' => 'El estado es obligatorio.',
            'status_id.exists' => 'El estado seleccionado no existe.',
            'assignee_id.exists' => 'El agente asignado no existe.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
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
        ];
    }
}
