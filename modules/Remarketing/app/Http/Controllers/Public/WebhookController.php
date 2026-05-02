<?php

namespace Modules\Remarketing\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Remarketing\Connectors\ConnectorRegistry;
use Modules\Remarketing\Jobs\ProcessBounceJob;
use Modules\Remarketing\Jobs\ProcessWebhookJob;
use Modules\Remarketing\Models\Store;

class WebhookController extends Controller
{
    public function __construct(
        protected ConnectorRegistry $registry,
    ) {}

    public function shopify(Request $request, string $storeToken): JsonResponse
    {
        return $this->dispatchPlatform($request, $storeToken, 'shopify', $request->header('X-Shopify-Topic', ''));
    }

    public function woocommerce(Request $request, string $storeToken): JsonResponse
    {
        return $this->dispatchPlatform($request, $storeToken, 'woocommerce', $request->header('X-WC-Webhook-Topic', ''));
    }

    public function prestashop(Request $request, string $storeToken): JsonResponse
    {
        $topic = $request->header('X-Remarketing-Topic')
            ?: $request->header('X-WC-Webhook-Topic')
            ?: $request->input('topic', '');

        return $this->dispatchPlatform($request, $storeToken, 'prestashop', (string) $topic);
    }

    public function emailEvents(Request $request, string $provider): JsonResponse
    {
        $payload = $request->all();
        $events = data_get($payload, 'events', $payload);

        if (! is_array($events) || array_is_list($events) === false) {
            $events = [$events];
        }

        foreach ($events as $event) {
            $type = data_get($event, 'event_type', data_get($event, 'event', 'delivery'));
            $email = data_get($event, 'email', '');
            $messageId = data_get($event, 'message_id');
            $storeId = (int) data_get($event, 'store_id', 0);

            if (! $email || ! $storeId) {
                continue;
            }

            ProcessBounceJob::dispatch($storeId, $email, $type, $messageId)
                ->onQueue('remarketing');
        }

        return response()->json(['ok' => true]);
    }

    protected function dispatchPlatform(Request $request, string $storeToken, string $platform, string $topic): JsonResponse
    {
        $store = Store::query()
            ->where('webhook_token', $storeToken)
            ->where('platform', $platform)
            ->first();

        if (! $store) {
            return response()->json(['ok' => false], 404);
        }

        try {
            $connector = $this->registry->for($store);

            if (! $connector->verifyWebhook($request)) {
                return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'connector error'], 500);
        }

        ProcessWebhookJob::dispatch($store, $topic, $request->all())
            ->onQueue('remarketing-webhooks');

        return response()->json(['ok' => true]);
    }
}
