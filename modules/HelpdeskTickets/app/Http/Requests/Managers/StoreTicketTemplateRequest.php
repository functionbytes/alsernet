<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'priority_id' => ['nullable', 'exists:helpdesk.helpdesk_priorities,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripcion no puede superar los 500 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'body.required' => 'El contenido es obligatorio.',
            'category_id.exists' => 'La categoria seleccionada no existe.',
            'priority_id.exists' => 'La prioridad seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'subject' => 'asunto',
            'body' => 'contenido',
            'category_id' => 'categoria',
            'priority_id' => 'prioridad',
            'is_active' => 'activo',
        ];
    }
}
