<?php

namespace Modules\HelpdeskChat\Http\Requests\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'status' => ['nullable', 'in:open,resolved,pending,closed,snoozed'],
            'custom_attributes' => ['nullable', 'array'],
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
            'subject.max' => 'El asunto no puede exceder :max caracteres.',
            'priority.in' => 'La prioridad debe ser: low, medium, high o urgent.',
            'status.in' => 'El estado debe ser: open, resolved, pending, closed o snoozed.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'assignee_id.exists' => 'El agente asignado no existe.',
        ];
    }
}
