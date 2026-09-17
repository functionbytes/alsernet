<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\WebhookDelivery;
use Modules\Helpdesk\Support\OutboundMediaUrlGuard;

/** Replays an already-rendered webhook payload from the delivery history. */
class ReplayWebhookDeliveryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(private readonly int $deliveryId)
    {
        $this->onQueue(config('helpdesk.queue.webhooks', 'helpdesk-webhooks'));
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->with('webhook')->find($this->deliveryId);
        $webhook = $delivery?->webhook;

        if (! $delivery || ! $webhook) {
            return;
        }

        if (! OutboundMediaUrlGuard::isAllowed($webhook->url)) {
            throw new \RuntimeException('La URL del webhook está bloqueada por la política SSRF.');
        }

        $bodyJson = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($bodyJson === false) {
            throw new \RuntimeException('No se pudo serializar el payload del webhook.');
        }

        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Helpdesk-Webhook/1.0',
            'X-Helpdesk-Event' => $delivery->event,
            'X-Helpdesk-Replay' => 'true',
        ], $webhook->headers ?? []);

        if ($webhook->secret) {
            $headers['X-Helpdesk-Signature'] = 'sha256='.hash_hmac('sha256', $bodyJson, $webhook->secret);
        }

        $start = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(20)
                ->withoutRedirecting()
                ->send('POST', $webhook->url, ['body' => $bodyJson]);

            $duration = (int) ((microtime(true) - $start) * 1000);
            WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'event' => $delivery->event,
                'payload' => $delivery->payload,
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
                'duration_ms' => $duration,
                'delivered_at' => now(),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('HTTP '.$response->status());
            }

            $webhook->increment('success_count');
            $webhook->update(['last_triggered_at' => now(), 'last_error' => null]);
        } catch (\Throwable $exception) {
            $webhook->increment('failure_count');
            $webhook->update(['last_error' => substr($exception->getMessage(), 0, 500)]);
            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ReplayWebhookDeliveryJob failed', [
            'delivery_id' => $this->deliveryId,
            'error' => $exception->getMessage(),
        ]);
    }
}
