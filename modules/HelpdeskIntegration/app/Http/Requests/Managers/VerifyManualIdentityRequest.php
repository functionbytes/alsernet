<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyManualIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permiso dedicado: confirmar identidad SIN código (a diferencia del
        // OTP por email/SMS) desbloquea de inmediato los datos sensibles de
        // integraciones (IDs externos, estado de conexión) — el mismo umbral
        // que exigen link()/unlink() (ver LinkCustomerIntegrationRequest).
        // Antes bastaba con 'view' (poder ver la ficha del cliente), el mismo
        // permiso mínimo que solo ver nombre/email/teléfono — cualquier
        // agente de solo-lectura podía saltarse el OTP con una sola petición.
        if (! $this->user()?->can('helpdesk.integrations.manage')) {
            return false;
        }

        return (bool) $this->user()->can('view', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'conversation_id' => [
                'nullable',
                'integer',
                Rule::exists('helpdesk.helpdesk_conversations', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.exists' => 'La conversación indicada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_id' => 'conversación',
        ];
    }
}
