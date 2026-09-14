<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Helpdesk\Models\AutomationRule;
use Modules\Helpdesk\Services\Automation\ConditionEvaluator;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.automation-rules.create') ?? false;
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

    /**
     * Anti-ReDoS: valida en el guardado los patrones de condiciones `regex`
     * con la misma lógica que usará ConditionEvaluator al evaluar.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $conditions = json_decode((string) $this->input('conditions_json'), true);

            if (! is_array($conditions)) {
                return;
            }

            $invalid = ConditionEvaluator::firstInvalidRegexPattern($conditions);

            if ($invalid !== null) {
                $validator->errors()->add('conditions_json', sprintf(
                    'La expresión regular "%s" no es válida o supera los %d caracteres.',
                    mb_substr($invalid, 0, 80),
                    ConditionEvaluator::MAX_REGEX_PATTERN_LENGTH
                ));
            }
        });
    }
}
