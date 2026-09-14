<?php

namespace Modules\HelpdeskSocial\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialAssignmentRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'assignee_user_id' => ['nullable', 'exists:users,id'],
            'assignment_strategy' => ['nullable', 'string', 'in:direct,round_robin,workload,intent'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignee_user_id.exists' => 'El agente seleccionado no es válido.',
            'assignment_strategy.in' => 'La estrategia seleccionada no es válida.',
            'priority.integer' => 'La prioridad debe ser un número entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'conditions' => 'condiciones',
            'assignee_user_id' => 'agente asignado',
            'assignment_strategy' => 'estrategia',
            'priority' => 'prioridad',
            'is_active' => 'estado',
        ];
    }
}
