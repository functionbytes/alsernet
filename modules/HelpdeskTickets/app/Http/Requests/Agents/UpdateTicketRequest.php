<?php

namespace Modules\HelpdeskTickets\Http\Requests\Agents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status_id' => ['sometimes', 'integer', 'exists:helpdesk_ticket_statuses,id'],
            'category_id' => ['sometimes', 'integer', 'exists:helpdesk_ticket_categories,id'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'status_id.exists' => 'El estado seleccionado no existe.',
            'category_id.exists' => 'La categoria seleccionada no existe.',
            'priority.in' => 'La prioridad seleccionada no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripcion',
            'status_id' => 'estado',
            'category_id' => 'categoria',
            'priority' => 'prioridad',
        ];
    }
}
