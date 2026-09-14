<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiAgentTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'regex:/^(fas|far|fab|fad) fa-[a-z0-9-]+$/'],
            'system_prompt_addition' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe ser un valor hexadecimal válido (ej: #90bb13).',
            'icon.regex' => 'El icono debe ser una clase Font Awesome válida (ej: fas fa-star).',
            'priority.integer' => 'La prioridad debe ser un número entero.',
            'priority.min' => 'La prioridad mínima es 0.',
            'priority.max' => 'La prioridad máxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'icono',
            'system_prompt_addition' => 'adicional al prompt del sistema',
            'priority' => 'prioridad',
            'is_active' => 'activo',
        ];
    }
}
