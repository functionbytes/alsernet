<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoutingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'max:255'],
            'match_type' => ['required', Rule::in(['contains', 'exact', 'regex'])],
            'assign_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assign_to_team_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.required' => 'La palabra clave es obligatoria.',
            'keyword.max' => 'La palabra clave no puede superar los 255 caracteres.',
            'match_type.required' => 'El tipo de coincidencia es obligatorio.',
            'match_type.in' => 'El tipo de coincidencia seleccionado no es válido.',
            'assign_to_user_id.exists' => 'El agente seleccionado no existe.',
            'priority.integer' => 'La prioridad debe ser un número entero.',
            'priority.min' => 'La prioridad no puede ser negativa.',
            'priority.max' => 'La prioridad no puede superar 9999.',
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword' => 'palabra clave',
            'match_type' => 'tipo de coincidencia',
            'assign_to_user_id' => 'agente asignado',
            'assign_to_team_id' => 'equipo asignado',
            'priority' => 'prioridad',
            'is_active' => 'activo',
        ];
    }
}
