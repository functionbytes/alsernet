<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Jobs\OutboundWebhookJob;
use Modules\Reviews\Models\ReviewWebhookSubscription;

class OutboundWebhookService
{
    /**
     * Dispatch outbound webhooks for all active subscriptions listening to the given event.
     */
    public function dispatch(string $event, array $payload): void
    {
        ReviewWebhookSubscription::query()
            ->active()
            ->forEvent($event)
            ->each(function (ReviewWebhookSubscription $subscription) use ($event, $payload): void {
                OutboundWebhookJob::dispatch($subscription, $event, $payload);
            });
    }

    /**
     * Send a webhook to a single subscription synchronously.
     */
    public function send(ReviewWebhookSubscription $subscription, string $event, array $payload): bool
    {
        $body = json_encode($payload);
        $signature = $subscription->secret
            ? hash_hmac('sha256', $body, $subscription->secret)
            : null;

        try {
            $request = Http::timeout(5)
                ->withHeaders(array_filter([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Signature' => $signature ? "sha256={$signature}" : null,
                ]));

            $response = $request->withBody($body, 'application/json')->post($subscription->url);

            if ($response->successful()) {
                $subscription->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);

                return true;
            }

            $this->handleFailure($subscription);

            Log::warning('Webhook delivery failed', [
                'subscription_id' => $subscription->id,
                'url' => $subscription->url,
                'event' => $event,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->handleFailure($subscription);

            Log::error('Webhook delivery exception', [
                'subscription_id' => $subscription->id,
                'url' => $subscription->url,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function handleFailure(ReviewWebhookSubscription $subscription): void
    {
        $newCount = $subscription->failure_count + 1;

        $subscription->update([
            'failure_count' => $newCount,
            'is_active' => $newCount < 5,
        ]);
    }
}
