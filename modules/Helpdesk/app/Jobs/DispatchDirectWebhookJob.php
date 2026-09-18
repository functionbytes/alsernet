<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;

/**
 * Entrega en cola de webhooks de automatización con URL directa (sin modelo
 * Webhook asociado). Antes SendWebhookAction hacía el POST síncrono dentro del
 * request/listener; ahora ambas ramas (webhook_id y url) van por cola.
 */
class DispatchDirectWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    /** @param array<string, mixed> $payload */
    public function __construct(
        private readonly string $url,
        private readonly array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        // Defensa SSRF: además de la validación al encolar, se revalida justo
        // antes del envío (igual que DispatchWebhookJob) por si la URL se
        // guardó antes de existir esta protección o para mitigar DNS rebinding
        // entre validación y envío.
        if (! OutboundUrlGuard::isSafe($this->url)) {
            Log::warning('DispatchDirectWebhookJob: URL bloqueada por SSRF guard', ['url' => $this->url]);

            return;
        }

        try {
            Http::timeout(10)->post($this->url, $this->payload);
        } catch (\Throwable $e) {
            Log::warning('DispatchDirectWebhookJob: direct POST failed', [
                'url' => $this->url,
                'error' => $e->getMessage(),
            ]);

            throw $e; // dejar que la cola aplique tries/backoff
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchDirectWebhookJob failed', [
            'url' => $this->url,
            'error' => $exception->getMessage(),
        ]);
    }
}
