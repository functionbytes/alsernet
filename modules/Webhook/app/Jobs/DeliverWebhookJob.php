<?php

namespace Modules\Webhook\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Webhook\Models\WebhookDelivery;
use Modules\Webhook\Services\WebhookDeliveryService;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        protected int $deliveryId,
        protected array $payload
    ) {}

    public function handle(WebhookDeliveryService $deliveryService): void
    {
        $delivery = WebhookDelivery::with('subscription')->find($this->deliveryId);

        if (! $delivery) {
            Log::error('Delivery not found', ['delivery_id' => $this->deliveryId]);

            return;
        }

        if ($delivery->status === 'success' || $delivery->status === 'dead') {
            return;
        }

        $delivery->update(['status' => 'sending']);
        $delivery->incrementAttempt();

        try {
            $result = $deliveryService->deliver($delivery, $this->payload);

            if ($result['success']) {
                $delivery->markAsSuccess($result['http_status'], $result['latency_ms']);

                Log::info('Webhook delivered successfully', [
                    'delivery_id' => $delivery->id,
                    'http_status' => $result['http_status'],
                ]);
            } else {
                $this->handleFailure($delivery, $result);
            }
        } catch (\Exception $e) {
            $this->handleFailure($delivery, [
                'success' => false,
                'error' => $e->getMessage(),
                'http_status' => null,
                'latency_ms' => 0,
            ]);
        }
    }

    protected function handleFailure(WebhookDelivery $delivery, array $result): void
    {
        $delivery->markAsFailed(
            $result['error'],
            $result['http_status'],
            $result['latency_ms']
        );

        Log::warning('Webhook delivery failed', [
            'delivery_id' => $delivery->id,
            'attempt' => $delivery->attempt_count,
            'http_status' => $result['http_status'],
            'error' => $result['error'],
        ]);

        if ($delivery->status === 'failed' && $delivery->next_retry_at) {
            $delay = $delivery->next_retry_at->diffInSeconds(now());

            self::dispatch($delivery->id, $this->payload)
                ->onQueue('deliveries')
                ->delay(now()->addSeconds($delay));

            Log::info('Retry scheduled', [
                'delivery_id' => $delivery->id,
                'next_retry_at' => $delivery->next_retry_at->toIso8601String(),
            ]);
        }
    }
}
