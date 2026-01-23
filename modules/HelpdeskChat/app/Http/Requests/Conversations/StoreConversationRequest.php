<?php

namespace Modules\HelpdeskChat\Http\Requests\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization can be added based on permissions
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'exists:helpdesk_contacts,id'],
            'inbox_id' => ['required', 'exists:helpdesk_inboxes,id'],
            'initial_message' => ['nullable', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'team_id' => ['nullable', 'exists:helpdesk_teams,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'El contacto es requerido.',
            'contact_id.exists' => 'El contacto seleccionado no existe.',
            'inbox_id.required' => 'El canal (inbox) es requerido.',
            'inbox_id.exists' => 'El canal seleccionado no existe.',
            'initial_message.max' => 'El mensaje inicial no puede exceder :max caracteres.',
            'subject.max' => 'El asunto no puede exceder :max caracteres.',
            'priority.in' => 'La prioridad debe ser: low, medium, high o urgent.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'assignee_id.exists' => 'El agente asignado no existe.',
        ];
    }
}
