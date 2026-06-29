<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\Remarketing\Jobs\SendCrossSellMailJob;

class CrossSellOnDeliveredListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsOrderStatusChanged $event): void
    {
        if ($event->newStatus() !== 'delivered') {
            return;
        }

        $customerId = $event->customerId();
        $orderId = $event->orderId();

        if (! $customerId || ! $orderId) {
            return;
        }

        SendCrossSellMailJob::dispatch($customerId, $orderId)
            ->delay(now()->addDays(7));
    }

    public function failed(PsOrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('CrossSellOnDeliveredListener failed', [
            'customer_id' => $event->customerId(),
            'order_id' => $event->orderId(),
            'error' => $exception->getMessage(),
        ]);
    }
}
