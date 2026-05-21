<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Remarketing\Services\StepHandlerRegistry;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.automations.create');
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
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', 'string', 'in:welcome,cart_abandoned,post_purchase,win_back,browse_abandoned'],
            'trigger_config' => ['nullable', 'array'],
            'goal_event' => ['nullable', 'string', 'max:60'],
            'goal_window_hours' => ['nullable', 'integer', 'between:1,8760'],
            'status' => ['nullable', 'string', 'in:draft,active,paused'],
            'steps' => ['nullable', 'array'],
            'steps.*.type' => ['required_with:steps', 'string', Rule::in(app(StepHandlerRegistry::class)->types())],
            'steps.*.config' => ['required_with:steps', 'array'],
            'steps.*.sort_order' => ['required_with:steps', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'trigger' => 'disparador',
            'steps' => 'pasos',
        ];
    }
}
