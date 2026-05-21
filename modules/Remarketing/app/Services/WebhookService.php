<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Jobs\DispatchWebhookJob;
use Modules\Remarketing\Models\Webhook;

class WebhookService
{
    public function fire(string $event, int $storeId, array $payload): void
    {
        Webhook::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $wh) => $wh->listensTo($event))
            ->each(fn (Webhook $wh) => DispatchWebhookJob::dispatch($wh, $event, $payload));
    }
}
