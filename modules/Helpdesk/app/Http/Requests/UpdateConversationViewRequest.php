<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.views.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'filters' => ['nullable', 'array'],
            'sort_by' => ['nullable', 'string', 'in:created_at,updated_at,priority,status,assignee_id'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'is_public' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la vista es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'sort_by.in' => 'El criterio de ordenación no es válido.',
            'sort_direction.in' => 'La dirección de ordenación debe ser ascendente o descendente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'filters' => 'filtros',
            'sort_by' => 'ordenar por',
            'sort_direction' => 'dirección',
            'is_public' => 'pública',
            'is_default' => 'predeterminada',
        ];
    }
}
