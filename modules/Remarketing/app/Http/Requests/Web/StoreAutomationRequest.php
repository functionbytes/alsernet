<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.automations.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', 'string', 'in:welcome,cart_abandoned,post_purchase,win_back,browse_abandoned'],
            'trigger_config' => ['nullable', 'array'],
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
            'trigger' => 'disparador',
            'steps' => 'pasos',
        ];
    }
}
