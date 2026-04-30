<?php

namespace Modules\Chat\Http\Requests\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:open,pending,resolved,closed',
            'status_id' => 'nullable|exists:chat_conversation_statuses,id',
            'language' => 'nullable|string|max:10',
            'team_id' => 'nullable|exists:chat_teams,id',
            'assignee_id' => 'nullable|exists:users,id',
            'sla_id' => 'nullable|exists:chat_sla_policies,id',
            'is_archived' => 'nullable|boolean',
            'custom_attributes' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.max' => 'The subject must not exceed 255 characters.',
            'priority.in' => 'The priority must be low, medium, high, or urgent.',
            'status.in' => 'The status must be open, pending, resolved, or closed.',
            'status_id.exists' => 'The selected status does not exist.',
            'language.max' => 'The language code must not exceed 10 characters.',
            'team_id.exists' => 'The selected team does not exist.',
            'assignee_id.exists' => 'The selected assignee does not exist.',
            'sla_id.exists' => 'The selected SLA policy does not exist.',
        ];
    }
}
