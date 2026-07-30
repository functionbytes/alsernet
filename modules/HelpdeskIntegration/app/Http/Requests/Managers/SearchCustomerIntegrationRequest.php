<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;

class SearchCustomerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can('view', $this->route('customer'))) {
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
            'q' => ['nullable', 'string', 'max:191'],
            'type' => ['nullable', 'string', 'max:30'],
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
        return [
            'platform' => 'plataforma',
            'q' => 'búsqueda',
            'type' => 'tipo',
        ];
    }
}
