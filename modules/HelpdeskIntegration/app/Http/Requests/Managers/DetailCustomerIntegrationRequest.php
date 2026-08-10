<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailCustomerIntegrationRequest extends FormRequest
{
    /**
     * Igual que search(): solo consulta la plataforma remota (no persiste
     * nada), así que deliberadamente NO exige identidad verificada.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->route('customer'));
    }

    /**
     * `platform` llega como segmento de ruta, no como input — se copia aquí
     * para poder validarlo con las mismas reglas que search() en vez de
     * dejarlo pasar sin comprobar contra el catálogo.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'platform' => $this->route('platform'),
        ]);
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
        return [
            'platform' => 'plataforma',
        ];
    }
}
