<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\Remarketing\Jobs\SendBackInStockMailJob;

class NotifyBackInStockToWatchersListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsBackInStock $event): void
    {
        $productId = $event->productId();
        if (! $productId) {
            return;
        }

        $watches = DB::table('remarketing_product_watches')
            ->where('external_product_id', $productId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->get();

        foreach ($watches as $watch) {
            SendBackInStockMailJob::dispatch($watch->customer_id, $productId, $event->payload);
        }
    }

    public function failed(PsBackInStock $event, \Throwable $exception): void
    {
        Log::error('NotifyBackInStockToWatchersListener failed', [
            'product_id' => $event->productId(),
            'error' => $exception->getMessage(),
        ]);
    }
}
