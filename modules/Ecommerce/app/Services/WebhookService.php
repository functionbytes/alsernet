<?php

namespace Modules\Ecommerce\Services;

use Modules\Core\Models\Setting;
use Modules\Ecommerce\Jobs\DispatchWebhookJob;

class WebhookService
{
    private const ALLOWED_EVENTS = [
        'order.created',
        'order.paid',
        'order.shipped',
        'order.completed',
        'order.cancelled',
        'order.returned',
    ];

    public function dispatch(string $event, array $payload): void
    {
        if (! in_array($event, self::ALLOWED_EVENTS, true)) {
            return;
        }

        $url = Setting::get('ecommerce.order_placed_webhook_url');
        $secret = Setting::get('ecommerce.webhook_secret');

        if (! $url) {
            return;
        }

        DispatchWebhookJob::dispatch($event, $url, [
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'data' => $payload,
        ], $secret ?: null);
    }
}
