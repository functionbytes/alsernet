<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\WebhooksController;
use Modules\Helpdesk\Support\OutboundMediaUrlGuard;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.webhooks.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500', $this->publicUrlRule()],
            'integration_type' => ['nullable', 'string', 'in:generic,slack,discord,teams'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(WebhooksController::AVAILABLE_EVENTS))],
            'secret' => ['nullable', 'string', 'max:64'],
            'headers' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Regla SSRF: la URL del webhook saliente debe resolver a una IP pública
     * (no privada/reservada/loopback), para que no se use como sonda de la red
     * interna ni contra la metadata cloud (169.254.169.254).
     */
    protected function publicUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && $value !== '' && ! OutboundMediaUrlGuard::isAllowed($value)) {
                $fail('La URL debe ser pública: no se permiten IPs internas, privadas ni loopback.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato valido.',
            'url.max' => 'La URL no puede superar los 500 caracteres.',
            'events.required' => 'Debes seleccionar al menos un evento.',
            'events.min' => 'Debes seleccionar al menos un evento.',
            'events.*.in' => 'Uno o más eventos seleccionados no son válidos.',
            'secret.max' => 'El secreto no puede superar los 64 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'url' => 'URL',
            'integration_type' => 'tipo de integración',
            'events' => 'eventos',
            'secret' => 'secreto',
            'headers' => 'cabeceras',
            'is_active' => 'activo',
        ];
    }
}
