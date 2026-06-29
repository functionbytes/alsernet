<?php

namespace Modules\HelpdeskTickets\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.tickets.create');
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'integer', 'exists:helpdesk_ticket_categories,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'customer_id' => ['nullable', 'integer', 'exists:helpdesk_customers,id'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'priority.in' => 'La prioridad debe ser: low, normal, high o urgent.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'customer_email.email' => 'El email del cliente no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripción',
            'category_id' => 'categoría',
            'priority' => 'prioridad',
            'customer_id' => 'cliente',
            'customer_email' => 'email del cliente',
            'customer_name' => 'nombre del cliente',
        ];
    }
}
