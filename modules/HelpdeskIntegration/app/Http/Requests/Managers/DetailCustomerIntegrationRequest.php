<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailCustomerIntegrationRequest extends FormRequest
{
    /**
     * A diferencia de search() (candidatos de una búsqueda remota), esto
     * expone la ficha completa de una integración ya vinculada — mismos
     * datos que show()->buildPayload(). El gate de identidad verificada se
     * aplica en el controller (assertIdentityVerified), no aquí: authorize()
     * solo cubre el permiso base sobre el customer.
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
