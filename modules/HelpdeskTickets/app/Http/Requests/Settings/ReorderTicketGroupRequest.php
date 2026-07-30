<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTicketGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:helpdesk.helpdesk_ticket_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Los IDs son obligatorios.',
            'ids.array' => 'Los IDs deben ser un arreglo.',
            'ids.*.exists' => 'El grupo seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identificadores',
        ];
    }
}
