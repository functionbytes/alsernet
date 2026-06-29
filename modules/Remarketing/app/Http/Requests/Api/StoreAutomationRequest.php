<?php

namespace Modules\Remarketing\Http\Requests\Api;

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
            'trigger' => ['required', 'string', 'max:100'],
            'trigger_config' => ['nullable', 'array'],
            'steps' => ['nullable', 'array'],
            'steps.*.sort_order' => ['required_with:steps', 'integer', 'min:0'],
            'steps.*.type' => ['required_with:steps', 'string', 'max:100'],
            'steps.*.config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'name.required' => 'El nombre de la automatización es obligatorio.',
            'trigger.required' => 'El trigger es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'name' => 'nombre',
            'trigger' => 'disparador',
            'trigger_config' => 'configuración del disparador',
            'steps' => 'pasos',
        ];
    }
}
