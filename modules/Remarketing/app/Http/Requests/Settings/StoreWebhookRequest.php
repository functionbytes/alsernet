<?php

namespace Modules\Remarketing\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.settings.update');
    }

    public function rules(): array
    {
        $storeRule = Rule::exists('remarketing_stores', 'id');

        if (! $this->user()?->can('remarketing.manage')) {
            $storeRule = $storeRule->where('user_id', $this->user()->id);
        }

        return [
            'store_id' => ['required', 'integer', $storeRule],
            'url' => ['required', 'url', 'max:500', $this->webhookUrlRule()],
            'secret' => ['nullable', 'string', 'max:64'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in($this->allowedEvents())],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'La tienda es obligatoria.',
            'store_id.exists' => 'La tienda seleccionada no existe o no tienes acceso a ella.',
            'url.required' => 'La URL del endpoint es obligatoria.',
            'url.url' => 'La URL no tiene un formato válido.',
            'events.required' => 'Selecciona al menos un evento.',
            'events.min' => 'Selecciona al menos un evento.',
            'events.*.in' => 'El evento seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'url' => 'URL del endpoint',
            'secret' => 'secreto',
            'events' => 'eventos',
        ];
    }

    private function webhookUrlRule(): \Closure
    {
        return function ($attribute, $value, $fail): void {
            $parsed = parse_url($value);
            $scheme = $parsed['scheme'] ?? '';
            $host = $parsed['host'] ?? '';

            if ($scheme !== 'https') {
                $fail('La URL del webhook debe usar HTTPS.');

                return;
            }

            if (! $host || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
                $fail('No se permiten URLs internas como destino de webhook.');

                return;
            }

            $ips = @gethostbynamel($host) ?: [];

            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    $fail('No se permiten URLs internas como destino de webhook.');

                    return;
                }
            }
        };
    }

    private function allowedEvents(): array
    {
        return [
            'campaign.sent',
            'campaign.completed',
            'message.opened',
            'message.clicked',
            'message.bounced',
            'customer.unsubscribed',
            'automation.triggered',
            '*',
        ];
    }
}
