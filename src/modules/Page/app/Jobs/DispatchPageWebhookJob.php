<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\PageWebhook;
use Modules\Page\Services\WebhookUrlValidator;

class DispatchPageWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $webhookId,
        public readonly string $event,
        public readonly array $payload,
    ) {
        $this->queue = 'webhooks';
    }

    public function handle(): void
    {
        $webhook = PageWebhook::find($this->webhookId);

        if (! $webhook || ! $webhook->is_active) {
            return;
        }

        try {
            WebhookUrlValidator::validate($webhook->url);
        } catch (\InvalidArgumentException $e) {
            $this->fail($e);

            return;
        }

        $timestamp = now()->timestamp;

        $body = json_encode([
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $this->event,
            'X-Webhook-Timestamp' => (string) $timestamp,
            'User-Agent' => 'InoqualabsWebhook/1.0',
        ];

        if ($webhook->secret) {
            $headers['X-Webhook-Signature'] = 'sha256='.$webhook->generateSignature($body, $timestamp);
        }

        try {
            Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, json_decode($body, true));

            $webhook->increment('success_count');
            $webhook->update([
                'last_triggered_at' => now(),
                'last_success_at' => now(),
            ]);
        } catch (\Exception $e) {
            $webhook->increment('fail_count');
            $webhook->update(['last_triggered_at' => now()]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchPageWebhookJob failed permanently', [
            'webhook_id' => $this->webhookId,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}
