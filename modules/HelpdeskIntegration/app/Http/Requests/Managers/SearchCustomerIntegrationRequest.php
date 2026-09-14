<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchCustomerIntegrationRequest extends FormRequest
{
    /**
     * A diferencia de link()/unlink()/sync(), esta búsqueda no persiste
     * nada — solo consulta la plataforma remota — así que deliberadamente
     * NO exige identidad verificada. La usa el buscador de PrestaShop/ERP
     * del gate de identidad para ayudar a confirmar quién es el cliente
     * antes de verificarlo.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->route('customer'));
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
