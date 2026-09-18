<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;

class UnlinkCustomerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permiso dedicado: poder editar el cliente ya no basta para
        // desvincular integraciones (datos sensibles de plataformas externas).
        if (! $this->user()?->can('helpdesk.integrations.manage')) {
            return false;
        }

        if (! $this->user()->can('update', $this->route('customer'))) {
            return false;
        }

        return app(CustomerIdentityVerificationService::class)->isVerified($this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                'string',
                'max:50',
                Rule::exists('helpdesk.helpdesk_integration_providers', 'platform'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'La plataforma es obligatoria.',
            'platform.exists' => 'Plataforma no válida.',
        ];
    }

    public function attributes(): array
    {
        return ['platform' => 'plataforma'];
    }
}
