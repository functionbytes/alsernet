<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'priority_id' => ['nullable', 'exists:helpdesk.helpdesk_priorities,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'frequency' => ['required', 'in:daily,weekly,monthly,custom'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'next_run_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'subject.required' => 'El asunto es obligatorio.',
            'frequency.required' => 'La frecuencia es obligatoria.',
            'frequency.in' => 'La frecuencia debe ser daily, weekly, monthly o custom.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'description' => 'descripcion',
            'category_id' => 'categoria',
            'priority_id' => 'prioridad',
            'assignee_id' => 'agente asignado',
            'frequency' => 'frecuencia',
            'cron_expression' => 'expresion cron',
            'next_run_at' => 'proxima ejecucion',
            'is_active' => 'estado',
        ];
    }
}
