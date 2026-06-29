<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;

class PsEventReceiverController extends Controller
{
    private const DEDUP_TTL_SECONDS = 600;

    private const PROCESSING_TTL_SECONDS = 60;

    public function handle(Request $request): JsonResponse
    {
        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');
        $idemKey = $request->header('X-Alsernet-Idempotency-Key') ?: md5($timestamp.':'.$signature);
        $cacheKey = "ps_webhook_seen:{$idemKey}";

        // Two-phase idempotency: first mark as "processing" with short TTL.
        // If the same key already exists with "done", we deduplicate.
        $existing = Cache::get($cacheKey);

        if ($existing === 'done') {
            return response()->json(['ok' => true, 'deduplicated' => true]);
        }

        // Acquire processing lock. If another worker already has it, deduplicate.
        if ($existing === null && ! Cache::add($cacheKey, 'processing', self::PROCESSING_TTL_SECONDS)) {
            return response()->json(['ok' => true, 'deduplicated' => true]);
        }

        $event = (string) $request->header('X-Alsernet-Event', '');
        $payload = (array) $request->input('data', []);

        try {
            $dispatched = $this->dispatchEvent($event, $payload);
        } catch (\Throwable $e) {
            Log::error('PsEventReceiver: error dispatching event', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            // Release the lock so PS retries succeed instead of being deduplicated.
            Cache::forget($cacheKey);

            return response()->json(['ok' => false, 'error' => 'processing error'], 500);
        }

        // Promote to "done" with longer TTL once processing has completed.
        Cache::put($cacheKey, 'done', self::DEDUP_TTL_SECONDS);

        if (! $dispatched) {
            Log::info('PsEventReceiver: unknown event type', ['event' => $event]);
        }

        return response()->json(['ok' => true]);
    }

    private function dispatchEvent(string $event, array $payload): bool
    {
        return match ($event) {
            'order.created' => $this->fire(new PsOrderCreated($payload)),
            'order.status_changed' => $this->fire(new PsOrderStatusChanged($payload)),
            'order.return_requested' => $this->fire(new PsOrderReturned($payload)),
            'cart.abandoned' => $this->fire(new PsCartAbandoned($payload)),
            'product.price_dropped' => $this->fire(new PsPriceDropped($payload)),
            'product.back_in_stock' => $this->fire(new PsBackInStock($payload)),
            default => false,
        };
    }

    private function fire(object $eventObject): bool
    {
        event($eventObject);

        return true;
    }
}
