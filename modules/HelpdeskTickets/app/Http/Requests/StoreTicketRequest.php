<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Core\Rules\ValidMimeMagicBytes;

class StoreTicketRequest extends BaseTicketRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|max:50000',
            'category_id' => 'nullable|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'customer_id' => 'nullable|integer|exists:helpdesk_customers,id',
            'status_id' => 'nullable|integer|exists:helpdesk_ticket_statuses,id',
            'sla_policy_id' => 'nullable|integer|exists:helpdesk_ticket_sla_policies,id',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'group_id' => 'nullable|integer|exists:helpdesk_groups,id',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => [
                'file',
                'max:'.config('helpdesk.attachments.max_size', 10240),
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar,txt',
                new ValidMimeMagicBytes(config('helpdesk.attachments.allowed_mime_types', [])),
            ],
        ];
    }
}
