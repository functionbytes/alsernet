<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'filters' => ['nullable', 'array'],
            'sort_by' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
            'is_shared' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'sort_direction.in' => 'La direccion de ordenamiento debe ser asc o desc.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'sort_by' => 'ordenar por',
            'sort_direction' => 'direccion de ordenamiento',
            'is_shared' => 'compartida',
            'is_default' => 'predeterminada',
        ];
    }
}
