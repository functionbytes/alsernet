<?php

namespace Modules\Webhook\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'status' => 'required|in:active,suspended,disabled',
            'plan' => 'required|in:free,pro,enterprise',
            'daily_limit' => 'required|integer|min:1',
            'allowed_ips' => 'nullable|string',
            'allowed_domains' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'status.required' => 'El estado es obligatorio',
            'plan.required' => 'El plan es obligatorio',
        ];
    }
}
