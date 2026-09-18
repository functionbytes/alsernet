<?php

namespace Modules\HelpdeskCampaigns\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;

/**
 * Entrega un único webhook de campaña. Cada suscriptor va en su propio job para
 * que el reintento de un endpoint caído no re-entregue a los que ya recibieron
 * el evento (antes DispatchCampaignWebhooks iteraba todos y relanzaba).
 */
class DeliverCampaignWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public int $backoff = 60;

    /** @param array<string, mixed> $payload */
    public function __construct(
        private readonly string $url,
        private readonly ?string $secret,
        private readonly array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        // Revalidación anti-SSRF justo antes del envío (la URL viene de config,
        // pero OutboundUrlGuard además resuelve DNS y bloquea rangos privados,
        // cosa que el chequeo previo al encolar no cubría para hostnames).
        if (! OutboundUrlGuard::isSafe($this->url)) {
            Log::warning('DeliverCampaignWebhookJob: URL bloqueada por SSRF guard', [
                'host' => parse_url($this->url, PHP_URL_HOST),
            ]);

            return;
        }

        $headers = ['Content-Type' => 'application/json'];

        if (! empty($this->secret)) {
            $signature = hash_hmac('sha256', json_encode($this->payload), $this->secret);
            $headers['X-Helpdesk-Signature'] = "sha256={$signature}";
        }

        Http::timeout(8)
            ->withHeaders($headers)
            ->post($this->url, $this->payload)
            ->throw();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign webhook delivery failed permanently', [
            'host' => parse_url($this->url, PHP_URL_HOST),
            'event' => $this->payload['event'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
