<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Webhook;

class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public int $backoff = 60;

    public function __construct(
        private readonly Webhook $webhook,
        private readonly string $event,
        private readonly array $payload
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $body = json_encode([
            'event' => $this->event,
            'occurred_at' => now()->toIso8601String(),
            'payload' => $this->payload,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Remarketing-Event' => $this->event,
        ];

        if ($this->webhook->secret) {
            $headers['X-Remarketing-Signature'] = 'sha256='.hash_hmac('sha256', $body, $this->webhook->secret);
        }

        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->post($this->webhook->url, json_decode($body, true));

        if ($response->successful()) {
            $this->webhook->update([
                'failure_count' => 0,
                'last_triggered_at' => now(),
            ]);
        } else {
            $this->webhook->increment('failure_count');

            if ($this->webhook->fresh()->failure_count >= 10) {
                $this->webhook->update(['is_active' => false]);
                Log::warning('Remarketing webhook disabled after repeated failures', [
                    'webhook_id' => $this->webhook->id,
                    'url' => $this->webhook->url,
                ]);
            }

            $this->fail(new \RuntimeException("Webhook HTTP {$response->status()}: {$this->webhook->url}"));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchWebhookJob failed', [
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}
