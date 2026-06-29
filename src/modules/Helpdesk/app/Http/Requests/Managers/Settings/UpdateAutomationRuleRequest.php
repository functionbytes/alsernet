<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\AutomationRule;

class UpdateAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.automation-rules.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'event_name' => ['required', 'string', Rule::in(array_keys(AutomationRule::EVENTS))],
            'order' => ['nullable', 'integer', 'min:0'],
            'conditions_json' => ['nullable', 'string'],
            'actions_json' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'event_name.required' => 'El evento de disparo es obligatorio.',
            'event_name.in' => 'El evento seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'event_name' => 'evento de disparo',
            'order' => 'orden',
            'conditions_json' => 'condiciones',
            'actions_json' => 'acciones',
            'is_active' => 'activo',
        ];
    }
}
