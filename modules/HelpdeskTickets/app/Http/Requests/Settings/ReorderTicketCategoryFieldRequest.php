<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTicketCategoryFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Los elementos son obligatorios.',
            'items.array' => 'Los elementos deben ser un arreglo.',
            'items.*.id.required' => 'El identificador de cada elemento es obligatorio.',
            'items.*.sort_order.required' => 'El orden de cada elemento es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'elementos',
        ];
    }
}
