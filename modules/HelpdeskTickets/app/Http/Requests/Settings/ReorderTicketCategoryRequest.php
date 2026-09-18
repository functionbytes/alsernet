<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:helpdesk.helpdesk_ticket_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Los IDs son obligatorios.',
            'ids.array' => 'Los IDs deben ser un arreglo.',
            'ids.*.exists' => 'La categoria seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identificadores',
        ];
    }
}
