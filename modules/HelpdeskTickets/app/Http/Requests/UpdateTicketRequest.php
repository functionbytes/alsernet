<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTicketRequest extends BaseTicketRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('subject') && ! $this->filled('title')) {
            $this->merge(['title' => $this->input('subject')]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status_id' => 'sometimes|integer|exists:helpdesk_ticket_statuses,id',
            'category_id' => 'sometimes|nullable|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'assignee_id' => 'sometimes|nullable|integer',
            'group_id' => 'sometimes|nullable|integer|exists:helpdesk_groups,id',
            'sla_policy_id' => 'sometimes|nullable|integer|exists:helpdesk_ticket_sla_policies,id',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'string|max:100',
        ];
    }

    /**
     * Return only the fields that should be applied to the ticket.
     *
     * @return array<string, mixed>
     */
    public function getModifiableFields(): array
    {
        return $this->only([
            'title', 'subject', 'description', 'status_id', 'category_id',
            'priority', 'assignee_id', 'group_id', 'sla_policy_id', 'tags',
        ]);
    }
}
