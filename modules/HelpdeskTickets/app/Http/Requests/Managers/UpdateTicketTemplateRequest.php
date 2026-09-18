<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ticket_template')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'is_active' => ['nullable', 'boolean'],
            'is_general' => ['nullable', 'boolean'],
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
            'priority.in' => 'La prioridad seleccionada no es valida.',
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
            'priority' => 'prioridad',
            'is_active' => 'activo',
            'is_general' => 'alcance',
        ];
    }
}
