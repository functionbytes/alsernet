<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'ticket_ids' => ['required', 'array', 'min:1', 'max:100'],
            'ticket_ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:assign,close,reopen,change_status,delete,add_tag,assign_group'],
            'agent_id' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
            'status_id' => ['required_if:action,change_status', 'nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_statuses,id'],
            'group_id' => ['required_if:action,assign_group', 'nullable', 'integer', 'exists:helpdesk.helpdesk_groups,id'],
            'tag' => ['required_if:action,add_tag', 'nullable', 'string', 'max:50'],
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
            'group_id.required_if' => 'El grupo es obligatorio cuando la accion es asignar grupo.',
            'group_id.exists' => 'El grupo seleccionado no existe.',
            'tag.required_if' => 'La etiqueta es obligatoria cuando la accion es anadir etiqueta.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ticket_ids' => 'tickets',
            'action' => 'accion',
            'agent_id' => 'agente',
            'status_id' => 'estado',
            'group_id' => 'grupo',
            'tag' => 'etiqueta',
        ];
    }
}
