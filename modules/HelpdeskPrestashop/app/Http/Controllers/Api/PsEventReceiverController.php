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
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');

        if (! $secret) {
            return response()->json(['ok' => false, 'error' => 'not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);

        if ($timestamp === 0 || abs(time() - $timestamp) > 300) {
            return response()->json(['ok' => false, 'error' => 'invalid timestamp'], 401);
        }

        $rawBody = $request->getContent();
        $expected = hash_hmac('sha256', $timestamp.':'.$rawBody, $secret);
        $signature = (string) $request->header('X-Alsernet-Signature', '');

        if (! hash_equals($expected, $signature)) {
            Log::warning('PsEventReceiver: invalid HMAC signature.', ['ip' => $request->ip()]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        $idemKey = $request->header('X-Alsernet-Idempotency-Key') ?: md5($timestamp.':'.$signature);

        if (! Cache::add("ps_webhook_seen:{$idemKey}", true, 600)) {
            return response()->json(['ok' => true, 'deduplicated' => true]);
        }

        $event = (string) $request->header('X-Alsernet-Event', '');
        $payload = (array) ($request->input('data', []));

        try {
            match ($event) {
                'order.created' => event(new PsOrderCreated($payload)),
                'order.status_changed' => event(new PsOrderStatusChanged($payload)),
                'order.return_requested' => event(new PsOrderReturned($payload)),
                'cart.abandoned' => event(new PsCartAbandoned($payload)),
                'product.price_dropped' => event(new PsPriceDropped($payload)),
                'product.back_in_stock' => event(new PsBackInStock($payload)),
                default => Log::info('PsEventReceiver: unknown event type', ['event' => $event]),
            };
        } catch (\Throwable $e) {
            Log::error('PsEventReceiver: error dispatching event', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => 'processing error'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
