<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\OrderCompleted;
use Modules\Ecommerce\Services\OrderEmailService;

class SendProductReviewsMailAfterOrderCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(OrderCompleted $event): void
    {
        OrderEmailService::sendReviewRequest($event->order);
    }

    public function failed(OrderCompleted $event, \Throwable $exception): void
    {
        Log::error('SendProductReviewsMailAfterOrderCompleted failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
