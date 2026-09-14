<?php

namespace Modules\HelpdeskTickets\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.tickets.update');
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status_id' => ['sometimes', 'integer', 'exists:helpdesk.helpdesk_ticket_statuses,id'],
            'category_id' => ['sometimes', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'status_id.exists' => 'El estado seleccionado no existe.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'priority.in' => 'La prioridad debe ser: low, normal, high o urgent.',
            'assignee_id.exists' => 'El agente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripción',
            'status_id' => 'estado',
            'category_id' => 'categoría',
            'priority' => 'prioridad',
            'assignee_id' => 'agente asignado',
        ];
    }
}
