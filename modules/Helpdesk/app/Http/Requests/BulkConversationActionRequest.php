<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkConversationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'conversation_ids' => ['required', 'array', 'min:1', 'max:100'],
            'conversation_ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:assign,close,reopen,archive,delete'],
            'agent_id' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_ids.required' => 'Debes seleccionar al menos una conversación.',
            'conversation_ids.min' => 'Debes seleccionar al menos una conversación.',
            'conversation_ids.max' => 'No puedes seleccionar más de 100 conversaciones a la vez.',
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'agent_id.required_if' => 'El agente es obligatorio para la acción de asignación.',
            'agent_id.exists' => 'El agente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_ids' => 'conversaciones',
            'action' => 'acción',
            'agent_id' => 'agente',
        ];
    }
}
