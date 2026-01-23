<?php

namespace Modules\Webhook\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'integration_id' => 'required|exists:webhook_integrations,id',
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'subscribed_events' => 'required|array|min:1',
            'subscribed_events.*' => 'string',
            'auth_type' => 'nullable|in:none,bearer,basic,apikey,custom',
            'auth_config' => 'nullable|array',
            'timeout_ms' => 'nullable|integer|min:1000|max:60000',
            'max_attempts' => 'nullable|integer|min:1|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'integration_id.required' => 'Debe seleccionar una integración',
            'name.required' => 'El nombre es obligatorio',
            'url.required' => 'La URL es obligatoria',
            'url.url' => 'La URL debe ser válida',
            'subscribed_events.required' => 'Debe seleccionar al menos un evento',
        ];
    }
}
