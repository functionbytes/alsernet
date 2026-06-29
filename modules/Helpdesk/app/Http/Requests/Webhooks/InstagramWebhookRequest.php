<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class InstagramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->signatureIsValid();
    }

    public function rules(): array
    {
        return [];
    }

    private function signatureIsValid(): bool
    {
        // Instagram shares the Facebook app secret (same Meta platform)
        $appSecret = config('helpdesk.integrations.facebook.app_secret', '');

        if (! filled($appSecret)) {
            // Fail-closed: sin secreto no se puede verificar la firma. Solo se
            // permite en desarrollo local para no bloquear pruebas manuales.
            if (app()->environment('local')) {
                return true;
            }

            Log::warning('Webhook de Instagram rechazado: app_secret no configurado.');

            return false;
        }

        $signature = $this->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $provided = substr($signature, 7);
        $expected = hash_hmac('sha256', $this->getContent(), $appSecret);

        return hash_equals($expected, $provided);
    }
}
