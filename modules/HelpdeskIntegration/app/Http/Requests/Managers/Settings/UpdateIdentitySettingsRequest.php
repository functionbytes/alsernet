<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIdentitySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdeskintegration.providers.update');
    }

    public function rules(): array
    {
        return [
            'identity_sms_enabled' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'identity_sms_enabled' => 'verificación por SMS',
        ];
    }
}
