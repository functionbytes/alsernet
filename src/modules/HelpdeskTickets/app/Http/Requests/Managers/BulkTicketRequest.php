<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'ticket_ids' => ['required', 'array', 'min:1', 'max:100'],
            'ticket_ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:assign,close,reopen,change_status,resolve,delete'],
            'agent_id' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
            'status_id' => ['required_if:action,change_status', 'nullable', 'integer', 'exists:helpdesk_ticket_statuses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_ids.required' => 'Los tickets son obligatorios.',
            'ticket_ids.array' => 'Los tickets deben ser un arreglo.',
            'ticket_ids.min' => 'Debe seleccionar al menos un ticket.',
            'ticket_ids.max' => 'No puede seleccionar mas de 100 tickets.',
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'agent_id.required_if' => 'El agente es obligatorio cuando la accion es asignar.',
            'agent_id.exists' => 'El agente seleccionado no existe.',
            'status_id.required_if' => 'El estado es obligatorio cuando la accion es cambiar estado.',
            'status_id.exists' => 'El estado seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ticket_ids' => 'tickets',
            'action' => 'accion',
            'agent_id' => 'agente',
            'status_id' => 'estado',
        ];
    }
}
