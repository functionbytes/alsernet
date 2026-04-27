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
        ];
    }

    public function attributes(): array
    {
        return [
            'tag_id' => 'etiqueta',
            'priority' => 'prioridad',
            'assignee_id' => 'usuario asignado',
        ];
    }
}
