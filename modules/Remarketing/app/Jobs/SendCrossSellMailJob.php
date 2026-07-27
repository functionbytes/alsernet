<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Remarketing\Mail\CrossSellMail;
use Modules\Remarketing\Models\AutomationTriggerLog;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Suppression;

/**
 * 7 days after delivery, send cross-sell email with related products the customer has not purchased.
 *
 * Schema notes:
 * - remarketing_products has no category_id column; uses `collections` (JSON array of collection names)
 * - Products are matched by shared collections with the original order's products
 * - Filters: status='active', inventory > 0
 */
class SendCrossSellMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        private readonly int $customerId,
        private readonly int $orderId,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $customer = Customer::find($this->customerId);

        if (! $customer || ! $customer->email) {
            return;
        }

        // Products from the original order (by remarketing_products.id)
        $orderProductIds = DB::table('remarketing_order_items')
            ->where('order_id', $this->orderId)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->all();

        if (empty($orderProductIds)) {
            $this->logTrigger($customer, 'no_products_in_order');

            return;
        }

        // Gather unique collections from those products
        $orderProducts = DB::table('remarketing_products')
            ->whereIn('id', $orderProductIds)
            ->whereNotNull('collections')
            ->pluck('collections')
            ->all();

        $collections = collect($orderProducts)
            ->flatMap(fn ($json) => json_decode($json, true) ?: [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($collections)) {
            $this->logTrigger($customer, 'no_collections_found');

            return;
        }

        // Products already purchased by this customer (avoid recommending them)
        $boughtProductIds = DB::table('remarketing_order_items as oi')
            ->join('remarketing_orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.customer_id', $this->customerId)
            ->whereNotNull('oi.product_id')
            ->pluck('oi.product_id')
            ->unique()
            ->all();

        // Find active in-stock products from the same collections, not yet purchased
        $query = DB::table('remarketing_products')
            ->where('store_id', $customer->store_id)
            ->where('status', 'active')
            ->where('inventory', '>', 0)
            ->whereNotIn('id', empty($boughtProductIds) ? [0] : $boughtProductIds);

        // Filter by collections: product must share at least one collection
        $query->where(function ($q) use ($collections): void {
            foreach ($collections as $collection) {
                $q->orWhereRaw('JSON_CONTAINS(collections, ?)', [json_encode($collection)]);
            }
        });

        $recommendations = $query->limit(5)->get();

        if ($recommendations->count() < 2) {
            $this->logTrigger($customer, 'insufficient_recommendations');

            return;
        }

        if (Suppression::query()
            ->where('store_id', $customer->store_id)
            ->where('email', strtolower(trim($customer->email)))
            ->exists()
        ) {
            $this->logTrigger($customer, 'suppressed');

            return;
        }

        try {
            Mail::to($customer->email)->send(
                new CrossSellMail($customer, $recommendations->all())
            );
            $this->logTrigger($customer, null, true);
        } catch (\Throwable $e) {
            Log::warning('SendCrossSellMailJob failed', [
                'customer_id' => $customer->id,
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
            $this->logTrigger($customer, 'send_error');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCrossSellMailJob permanently failed', [
            'customer_id' => $this->customerId,
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }

    private function logTrigger(Customer $customer, ?string $skipReason, bool $sent = false): void
    {
        AutomationTriggerLog::create([
            'trigger_type' => 'cross_sell',
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
            'context' => ['order_id' => $this->orderId],
            'email_sent' => $sent,
            'skip_reason' => $skipReason,
            'triggered_at' => now(),
        ]);
    }
}
