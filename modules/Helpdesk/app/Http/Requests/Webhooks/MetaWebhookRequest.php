<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Base común para los webhooks de Meta (WhatsApp / Facebook / Instagram): todos
 * verifican la firma HMAC `X-Hub-Signature-256` sobre el cuerpo crudo con el
 * `app_secret` del canal. Las subclases solo aportan la clave de config del
 * secreto y una etiqueta para los logs.
 */
abstract class MetaWebhookRequest extends FormRequest
{
    /** Clave de config del app_secret del canal (p. ej. helpdesk.integrations.whatsapp.app_secret). */
    abstract protected function appSecretConfigKey(): string;

    /** Etiqueta del canal para los mensajes de log ("WhatsApp", "Facebook", "Instagram"). */
    abstract protected function channelLabel(): string;

    public function authorize(): bool
    {
        return $this->signatureIsValid();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    private function signatureIsValid(): bool
    {
        $appSecret = (string) config($this->appSecretConfigKey(), '');

        if (! filled($appSecret)) {
            // Fail-closed: sin secreto no se puede verificar la firma. Solo se
            // permite en desarrollo local para no bloquear pruebas manuales.
            if (app()->environment('local')) {
                return true;
            }

            Log::warning("Webhook de {$this->channelLabel()} rechazado: app_secret no configurado.");

            return false;
        }

        $signature = $this->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $this->getContent(), $appSecret);

        return hash_equals($expected, substr($signature, 7));
    }
}
