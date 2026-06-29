<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\WebhookLog;

class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 60;

    public function __construct(
        public readonly string $event,
        public readonly string $url,
        public readonly array $payload,
        public readonly ?string $secret = null,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $log = WebhookLog::query()->create([
            'event' => $this->event,
            'url' => $this->url,
            'payload' => $this->payload,
            'attempts' => $this->attempts(),
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Alsernet-Ecommerce-Webhook/1.0',
        ];

        if ($this->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($this->payload), $this->secret);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->post($this->url, $this->payload);

            $log->update([
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
                'success' => $response->successful(),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("Webhook returned status {$response->status()}");
            }
        } catch (\Throwable $e) {
            $log->update([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed permanently', [
            'event' => $this->event,
            'url' => $this->url,
            'error' => $exception->getMessage(),
        ]);
    }
}
