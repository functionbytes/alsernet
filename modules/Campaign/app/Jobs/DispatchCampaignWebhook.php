<?php

namespace Modules\Campaign\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignWebhook;

/**
 * Dispara un webhook de campaña a un endpoint externo.
 * Usa backoff exponencial (1m → 5m → 15m → 1h) con max 4 intentos.
 *
 * Payload típico:
 * {
 *   "event": "sent" | "opened" | "clicked" | "bounced" | "feedback",
 *   "campaign_uid": "...",
 *   "subscriber_uid": "...",
 *   "email": "...",
 *   "message_id": "...",
 *   "timestamp": "2026-04-28T..."
 * }
 */
class DispatchCampaignWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $timeout = 30;

    public function __construct(
        protected int $webhookId,
        protected array $payload,
    ) {}

    /**
     * Backoff (segundos): 1m, 5m, 15m, 1h.
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(): void
    {
        $webhook = CampaignWebhook::find($this->webhookId);

        if (! $webhook || ! $webhook->enabled) {
            return;
        }

        $timestamp = now()->getTimestamp();
        $this->payload['timestamp'] = $timestamp;

        $body = json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Campaign-Webhook/1.0',
            'X-Webhook-Event' => $this->payload['event'] ?? 'unknown',
            'X-Webhook-Delivery' => (string) Str::uuid(),
            'X-Webhook-Timestamp' => (string) $timestamp,
        ];

        if (! empty($webhook->secret)) {
            $signaturePayload = $timestamp.'.'.$body;
            $signature = hash_hmac('sha256', $signaturePayload, (string) $webhook->secret);
            $headers['X-Webhook-Signature'] = "sha256={$signature}";
        }

        if (! empty($webhook->headers) && is_array($webhook->headers)) {
            $headers = array_merge($headers, $webhook->headers);
        }

        $method = strtolower($webhook->method ?: 'post');

        $request = Http::timeout($this->timeout)->withHeaders($headers);

        $response = match ($method) {
            'post' => $request->withBody($body, 'application/json')->post($webhook->url),
            'put' => $request->withBody($body, 'application/json')->put($webhook->url),
            'patch' => $request->withBody($body, 'application/json')->patch($webhook->url),
            'get' => $request->get($webhook->url, $this->payload),
            default => throw new Exception("Método HTTP no soportado: {$webhook->method}"),
        };

        if (! $response->successful()) {
            throw new Exception(sprintf(
                'Webhook %s respondió HTTP %d: %s',
                $webhook->url,
                $response->status(),
                substr($response->body(), 0, 200),
            ));
        }
    }
}
