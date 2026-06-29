<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\ReviewWebhookSubscription;
use Modules\Reviews\Services\OutboundWebhookService;

class OutboundWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReviewWebhookSubscription $subscription,
        public readonly string $event,
        public readonly array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(OutboundWebhookService $service): void
    {
        $service->send($this->subscription, $this->event, $this->payload);
    }
}
