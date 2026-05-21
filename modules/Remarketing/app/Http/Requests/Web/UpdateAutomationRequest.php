<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.automations.update');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('steps_json')) {
            $decoded = json_decode($this->input('steps_json'), true);
            $this->merge([
                'steps' => is_array($decoded) ? $decoded : [],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_config' => ['nullable', 'array'],
            'goal_event' => ['nullable', 'string', 'max:60'],
            'goal_window_hours' => ['nullable', 'integer', 'between:1,8760'],
            'status' => ['nullable', 'string', 'in:active,paused,draft'],
            'steps' => ['nullable', 'array'],
            'steps.*.type' => ['required_with:steps', 'string', 'in:wait,send_email'],
            'steps.*.config' => ['required_with:steps', 'array'],
            'steps.*.sort_order' => ['required_with:steps', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'status' => 'estado',
            'steps' => 'pasos',
        ];
    }
}
