<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Customer;

/**
 * Detect customers who viewed a product 1-2h ago but did not add it to cart or purchase.
 * Schedule: hourly.
 *
 * Uses remarketing_events (type='product_view') which is the module's own event log.
 * Properties JSON is expected to contain: product_id, product_title, product_url, product_image_url, price.
 */
class DetectBrowseAbandonmentJob implements ShouldQueue
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
        $cutoffStart = now()->subHours(2);
        $cutoffEnd = now()->subHour();

        // Get distinct customer+product combinations from the browse window
        $browseEvents = DB::table('remarketing_events as e')
            ->where('e.type', 'product_view')
            ->whereNotNull('e.customer_id')
            ->whereBetween('e.occurred_at', [$cutoffStart, $cutoffEnd])
            ->select('e.customer_id', 'e.properties', 'e.occurred_at')
            ->orderBy('e.occurred_at')
            ->limit(500)
            ->get();

        $dispatched = 0;

        foreach ($browseEvents as $event) {
            $properties = json_decode($event->properties ?? '{}', true) ?: [];
            $productId = $properties['product_id'] ?? null;

            if (! $productId) {
                continue;
            }

            $customerId = (int) $event->customer_id;
            $productIdStr = (string) $productId;

            // Skip if customer purchased this product after browsing
            $hasOrderSince = DB::table('remarketing_order_items as oi')
                ->join('remarketing_orders as o', 'o.id', '=', 'oi.order_id')
                ->where('o.customer_id', $customerId)
                ->where('oi.external_product_id', $productIdStr)
                ->where('o.placed_at', '>', $event->occurred_at)
                ->exists();

            if ($hasOrderSince) {
                continue;
            }

            // Skip if already sent browse_abandonment for this customer+product in last 24h
            $alreadySent = DB::table('remarketing_automation_triggers_log')
                ->where('customer_id', $customerId)
                ->where('trigger_type', 'browse_abandonment')
                ->where('triggered_at', '>=', now()->subDay())
                ->whereRaw("JSON_EXTRACT(context, '$.product_id') = ?", [$productId])
                ->exists();

            if ($alreadySent) {
                continue;
            }

            SendBrowseAbandonmentMailJob::dispatch($customerId, $productId, $properties);
            $dispatched++;
        }

        Log::info('DetectBrowseAbandonmentJob completed', [
            'events_checked' => $browseEvents->count(),
            'dispatched' => $dispatched,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DetectBrowseAbandonmentJob failed', ['error' => $e->getMessage()]);
    }
}
