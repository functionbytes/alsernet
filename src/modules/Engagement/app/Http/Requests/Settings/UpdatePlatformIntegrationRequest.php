<?php

namespace Modules\Engagement\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('engagement.platforms.update') ?? false;
    }

    public function rules(): array
    {
        return [
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
            'store_url.url' => 'La URL del store debe ser válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_url' => 'URL del store',
            'config.auth_token' => 'token de autenticación',
        ];
    }
}
