<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Services\OrderEmailService;

class OrderCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(OrderCancelled $event): void
    {
        OrderEmailService::sendOrderCancelled($event->order);
    }

    public function failed(OrderCancelled $event, \Throwable $exception): void
    {
        Log::error('OrderCancelledNotification failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
