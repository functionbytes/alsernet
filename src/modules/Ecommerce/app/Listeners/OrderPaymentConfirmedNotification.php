<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\OrderPaymentConfirmed;
use Modules\Ecommerce\Services\OrderEmailService;

class OrderPaymentConfirmedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(OrderPaymentConfirmed $event): void
    {
        OrderEmailService::sendPaymentConfirmed($event->order);
    }

    public function failed(OrderPaymentConfirmed $event, \Throwable $exception): void
    {
        Log::error('OrderPaymentConfirmedNotification failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
