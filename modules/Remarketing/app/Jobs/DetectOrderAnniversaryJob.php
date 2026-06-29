<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Order;

/**
 * Detect orders placed exactly 365 days ago to send anniversary emails.
 * Schedule: daily at 11:00.
 */
class DetectOrderAnniversaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $targetStart = now()->subDays(365)->startOfDay();
        $targetEnd = now()->subDays(365)->endOfDay();

        $dispatched = 0;

        Order::query()
            ->whereBetween('placed_at', [$targetStart, $targetEnd])
            ->with('customer')
            ->whereHas('customer')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$dispatched): void {
                foreach ($orders as $order) {
                    if (! $order->customer || ! $order->customer->email) {
                        continue;
                    }

                    $alreadySent = DB::table('remarketing_automation_triggers_log')
                        ->where('customer_id', $order->customer_id)
                        ->where('trigger_type', 'order_anniversary')
                        ->whereRaw("JSON_EXTRACT(context, '$.order_id') = ?", [$order->id])
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    SendOrderAnniversaryMailJob::dispatch(
                        (int) $order->customer_id,
                        (int) $order->id,
                    );

                    $dispatched++;
                }
            });

        Log::info('DetectOrderAnniversaryJob completed', ['dispatched' => $dispatched]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DetectOrderAnniversaryJob failed', ['error' => $e->getMessage()]);
    }
}
