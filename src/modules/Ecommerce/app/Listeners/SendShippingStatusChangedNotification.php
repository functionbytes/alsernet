<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\ShippingStatusChanged;
use Modules\Ecommerce\Services\OrderEmailService;

class SendShippingStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(ShippingStatusChanged $event): void
    {
        $order = $event->shipment->order;
        if (! $order) {
            return;
        }

        OrderEmailService::sendOrderShipped($order, $event->shipment);
    }

    public function failed(ShippingStatusChanged $event, \Throwable $exception): void
    {
        Log::error('SendShippingStatusChangedNotification failed', [
            'shipment_id' => $event->shipment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
