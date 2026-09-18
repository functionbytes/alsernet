<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;

class LinkCustomerIntegrationRequest extends FormRequest
{
    /**
     * Decisión explícita del usuario (ago-2026): vincular ya no exige
     * identidad verificada — el buscador de plataformas del gate de
     * identidad permite vincular directamente, antes de verificar, para
     * no bloquear al agente que ya confirmó visualmente el match. sync()/
     * unlink() siguen exigiendo verificación (acciones sobre un vínculo ya
     * establecido, no sobre un candidato recién encontrado).
     */
    public function authorize(): bool
    {
        // Permiso dedicado: poder editar el cliente ya no basta para
        // vincular integraciones (datos sensibles de plataformas externas).
        if (! $this->user()?->can('helpdesk.integrations.manage')) {
            return false;
        }

        return (bool) $this->user()?->can('update', $this->route('customer'));
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
