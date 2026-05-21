<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\Remarketing\Jobs\SendPriceDropMailJob;

class NotifyPriceDropToWatchersListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsPriceDropped $event): void
    {
        $productId = $event->productId();
        $oldPrice = $event->oldPrice();
        $newPrice = $event->newPrice();

        if (! $productId || $newPrice <= 0) {
            return;
        }

        $watches = DB::table('remarketing_product_watches')
            ->where('external_product_id', $productId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->get();

        foreach ($watches as $watch) {
            if ($watch->reference_price !== null && (float) $watch->reference_price < $oldPrice) {
                continue;
            }

            SendPriceDropMailJob::dispatch(
                $watch->customer_id,
                $productId,
                $oldPrice,
                $newPrice,
                $event->payload,
            );
        }
    }

    public function failed(PsPriceDropped $event, \Throwable $exception): void
    {
        Log::error('NotifyPriceDropToWatchersListener failed', [
            'product_id' => $event->productId(),
            'error' => $exception->getMessage(),
        ]);
    }
}
