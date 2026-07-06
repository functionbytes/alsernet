<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationAjaxActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        $action = $this->input('action');

        if (in_array($action, ['add_tag', 'remove_tag'], true)) {
            return [
                'tag_id' => ['required', 'exists:helpdesk.helpdesk_conversation_tags,id'],
            ];
        }

        if ($this->has('priority')) {
            return [
                'priority' => ['required', 'in:low,normal,high,urgent'],
            ];
        }

        if ($this->has('assignee_id')) {
            return [
                'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            ];
        }

        if ($this->has('status_id')) {
            return [
                'status_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_conversation_statuses,id'],
            ];
        }

        if ($this->has('tag_ids')) {
            return [
                'tag_ids' => ['nullable', 'array'],
                'tag_ids.*' => ['integer', 'exists:helpdesk.helpdesk_conversation_tags,id'],
            ];
        }

        if ($this->has('group_id')) {
            return [
                'group_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_groups,id'],
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'tag_id.required' => 'La etiqueta es obligatoria.',
            'tag_id.exists' => 'La etiqueta seleccionada no existe.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
            'assignee_id.exists' => 'El usuario asignado no existe.',
            'group_id.exists' => 'El equipo seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tag_id' => 'etiqueta',
            'priority' => 'prioridad',
            'assignee_id' => 'usuario asignado',
            'group_id' => 'equipo',
        ];
    }
}
