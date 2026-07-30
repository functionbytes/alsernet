<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreMacroRequest extends FormRequest
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
            'actions' => ['required', 'json'],
            'is_shared' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'actions.required' => 'Las acciones son obligatorias.',
            'actions.json' => 'Las acciones deben ser un JSON valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'actions' => 'acciones',
            'is_shared' => 'compartida',
            'is_active' => 'estado',
        ];
    }
}
