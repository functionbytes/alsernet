<?php

namespace Modules\Engagement\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StorePlatformIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('engagement.platforms.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'platform' => ['required', 'in:prestashop,shopify,woocommerce,erp,custom'],
            'store_url' => ['nullable', 'url', 'max:500'],
            'config' => ['nullable', 'array'],
            'config.auth_token' => ['nullable', 'string', 'max:1024'],
            'config.lookup_strategy' => ['nullable', 'in:email,external_id_only'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'inbox_id.required' => 'Debes seleccionar un inbox.',
            'inbox_id.exists' => 'El inbox seleccionado no existe.',
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.in' => 'Plataforma no soportada.',
            'store_url.url' => 'La URL del store debe ser válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inbox_id' => 'inbox',
            'store_url' => 'URL del store',
            'config.auth_token' => 'token de autenticación',
        ];
    }
}
