<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class LinkTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'linked_ticket_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_tickets,id'],
            'link_type' => ['nullable', 'in:related,duplicate_of,blocks,blocked_by'],
        ];
    }

    public function messages(): array
    {
        return [
            'linked_ticket_id.required' => 'El ticket a enlazar es obligatorio.',
            'linked_ticket_id.exists' => 'El ticket a enlazar no existe.',
            'link_type.in' => 'El tipo de enlace debe ser related, duplicate_of, blocks o blocked_by.',
        ];
    }

    public function attributes(): array
    {
        return [
            'linked_ticket_id' => 'ticket enlazado',
            'link_type' => 'tipo de enlace',
        ];
    }
}
