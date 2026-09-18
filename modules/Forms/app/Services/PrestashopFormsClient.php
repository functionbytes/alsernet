<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Forms\Support\HmacSigner;

/**
 * Publica en la tienda las definiciones de formulario compiladas aquí.
 *
 * Habla con el api.php del módulo alsernetforms, que guarda el artefacto en su
 * propia tabla y lo sirve por su cuenta: a partir de la publicación, la tienda
 * no vuelve a preguntar nada a este panel para renderizar el formulario.
 *
 * Mismo esquema de firma que el resto de módulos alsernet* (Reviews, Questions,
 * el bridge): hash_hmac('sha256', "{timestamp}:{body}", secreto compartido).
 * Aquí el secreto es el que ya comparten alsernetforms y este módulo para los
 * webhooks entrantes, así que no hay credencial nueva que repartir.
 */
class PrestashopFormsClient
{
    public function isConfigured(): bool
    {
        return $this->url() !== '' && $this->secret() !== '';
    }

    /**
     * @param  array<string, mixed>  $artifact  salida de FormArtifactBuilder::build()
     * @return array<string, mixed>
     */
    public function publish(array $artifact, ?string $idempotencyKey = null): array
    {
        return $this->call('form.publish', $artifact, $idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function unpublish(string $formKey): array
    {
        return $this->call('form.unpublish', ['form_key' => $formKey]);
    }

    /**
     * Decide quién sirve este formulario en la tienda: la definición publicada
     * o el .tpl de código de siempre. Es el interruptor de la migración, y es
     * reversible al instante.
     *
     * @return array<string, mixed>
     */
    public function setOverride(string $formKey, bool $overrides): array
    {
        return $this->call('form.set_override', [
            'form_key' => $formKey,
            'overrides_legacy' => $overrides,
        ]);
    }

    /**
     * Qué versión tiene la tienda ahora mismo. Sirve para detectar una tienda
     * restaurada desde copia de seguridad, que se habría quedado atrás sin que
     * el panel se enterase.
     *
     * @return array<string, mixed>
     */
    public function fetch(string $formKey): array
    {
        return $this->call('form.get', ['form_key' => $formKey]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function call(string $action, array $data, ?string $idempotencyKey = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'La conexión con la tienda no está configurada.'];
        }

        $timestamp = time();
        $body = json_encode(['action' => $action, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = HmacSigner::sign($this->secret(), $timestamp, $body);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Alsernet-Timestamp' => (string) $timestamp,
                'X-Alsernet-Signature' => $signature,
                'X-Alsernet-Idempotency-Key' => $idempotencyKey ?: (string) Str::uuid(),
            ])
                ->timeout((int) config('forms.store.http_timeout', 20))
                ->connectTimeout((int) config('forms.store.http_connect_timeout', 3))
                ->withBody($body, 'application/json')
                ->post($this->url());
        } catch (\Throwable $e) {
            Log::error('Forms: no se pudo llamar a la tienda.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($response->failed()) {
            Log::warning('Forms: la tienda rechazó la publicación.', [
                'action' => $action,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return [
                'ok' => false,
                'error' => 'HTTP '.$response->status().': '.mb_substr(strip_tags($response->body()), 0, 200),
                'status' => $response->status(),
            ];
        }

        return (array) $response->json();
    }

    private function url(): string
    {
        return trim((string) config('forms.store.api_url', ''));
    }

    private function secret(): string
    {
        // El mismo que verifica VerifyAlsernetFormsHmac en sentido contrario.
        return (string) config('forms.webhook_secret', '');
    }
}
