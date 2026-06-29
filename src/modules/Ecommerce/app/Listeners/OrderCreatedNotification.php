<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\OrderCreated;
use Modules\Ecommerce\Services\OrderEmailService;

class OrderCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(OrderCreated $event): void
    {
        OrderEmailService::sendOrderCreated($event->order);
    }

    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        Log::error('OrderCreatedNotification failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
