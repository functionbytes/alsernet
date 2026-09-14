<?php

namespace Modules\HelpdeskTickets\Http\Requests\Agents;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.assign') ?? false;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'agent_id.required' => 'El agente es obligatorio.',
            'agent_id.exists' => 'El agente seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'agent_id' => 'agente',
        ];
    }
}
