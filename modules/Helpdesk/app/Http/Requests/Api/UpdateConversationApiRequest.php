<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam subject string Subject/title of the conversation. Example: Actualización de pedido
 * @bodyParam priority string Priority. Enum: low,normal,high,urgent. Example: high
 * @bodyParam assignee_id integer Assign to agent user ID (null to unassign). Example: 3
 * @bodyParam status_id integer Conversation status ID. Example: 2
 */
class UpdateConversationApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'status_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_conversation_statuses,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'priority' => 'prioridad',
            'assignee_id' => 'agente asignado',
            'status_id' => 'estado',
        ];
    }
}
