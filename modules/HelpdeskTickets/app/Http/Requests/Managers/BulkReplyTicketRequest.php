<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:helpdesk.helpdesk_tickets,id'],
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_ids.required' => 'Los tickets son obligatorios.',
            'ticket_ids.array' => 'Los tickets deben ser un arreglo.',
            'ticket_ids.min' => 'Debe seleccionar al menos un ticket.',
            'ticket_ids.*.exists' => 'El ticket seleccionado no existe.',
            'body.required' => 'El mensaje es obligatorio.',
            'body.max' => 'El mensaje no puede superar los 10000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ticket_ids' => 'tickets',
            'body' => 'mensaje',
            'is_internal' => 'mensaje interno',
        ];
    }
}
