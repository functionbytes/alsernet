<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.automations.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'trigger' => ['sometimes', 'string', 'max:100'],
            'trigger_config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'trigger' => 'disparador',
            'trigger_config' => 'configuración del disparador',
        ];
    }
}
