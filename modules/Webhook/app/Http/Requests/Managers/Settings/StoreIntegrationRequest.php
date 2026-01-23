<?php

namespace Modules\Webhook\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'plan' => 'nullable|in:free,pro,enterprise',
            'daily_limit' => 'nullable|integer|min:1',
            'allowed_ips' => 'nullable|string',
            'allowed_domains' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la integración es obligatorio',
            'plan.in' => 'Plan inválido',
            'daily_limit.min' => 'El límite diario debe ser al menos 1',
        ];
    }
}
