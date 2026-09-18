<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiAgentToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:function,api,database,custom'],
            'parameters' => ['nullable', 'array'],
            'implementation' => ['nullable', 'string'],
            'auth_config' => ['nullable', 'array'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'parameters.array' => 'Los parámetros deben ser un conjunto válido.',
            'auth_config.array' => 'La configuración de autenticación debe ser un conjunto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo',
            'parameters' => 'parámetros',
            'implementation' => 'implementación',
            'auth_config' => 'configuración de autenticación',
            'requires_approval' => 'requiere aprobación',
            'is_active' => 'activo',
        ];
    }
}
