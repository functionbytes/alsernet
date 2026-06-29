<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class MergeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'merge_into_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_tickets,id', 'different:'.$ticket->id],
        ];
    }

    public function messages(): array
    {
        return [
            'merge_into_id.required' => 'El ticket de destino es obligatorio.',
            'merge_into_id.exists' => 'El ticket de destino no existe.',
            'merge_into_id.different' => 'No puedes fusionar un ticket consigo mismo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'merge_into_id' => 'ticket de destino',
        ];
    }
}
