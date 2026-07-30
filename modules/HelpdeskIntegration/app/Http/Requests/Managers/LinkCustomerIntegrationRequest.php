<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;

class LinkCustomerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permiso dedicado: poder editar el cliente ya no basta para
        // vincular integraciones (datos sensibles de plataformas externas).
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
                function (string $attribute, mixed $value, Closure $fail): void {
                    $provider = IntegrationProvider::query()->where('platform', $value)->first();

                    if (! $provider || ! $provider->is_active || ! $provider->is_linkable) {
                        $fail('Esta plataforma no está disponible para vincular.');
                    }
                },
            ],
            'external_id' => ['required', 'string', 'max:191'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'La plataforma es obligatoria.',
            'external_id.required' => 'El identificador externo es obligatorio.',
            'external_id.max' => 'El identificador externo no puede superar los 191 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'platform' => 'plataforma',
            'external_id' => 'identificador externo',
        ];
    }
}
