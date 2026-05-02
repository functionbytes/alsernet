<?php

namespace Modules\Remarketing\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\ConnectorRegistry;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Models\OrderItem;
use Modules\Remarketing\Models\Store;

class SyncOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 60;

    public function __construct(
        public readonly Store $store,
        public readonly ?Carbon $since = null,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(ConnectorRegistry $registry): void
    {
        $registry->for($this->store)->syncOrders(
            fn (array $chunk) => $this->upsertOrders($chunk),
            $this->since,
        );
    }

    private function upsertOrders(array $orders): void
    {
        foreach ($orders as $item) {
            $externalId = (string) ($item['external_id'] ?? $item['id'] ?? '');

            if (! $externalId || Order::where('store_id', $this->store->id)->where('external_id', $externalId)->exists()) {
                continue;
            }

            $customer = $this->resolveCustomer($item);

            $placedAt = $item['placed_at'] ?? $item['created_at'] ?? null;

            if ($placedAt && ! $placedAt instanceof Carbon) {
                $placedAt = Carbon::parse($placedAt);
            }

            $order = Order::create([
                'store_id' => $this->store->id,
                'customer_id' => $customer?->id,
                'external_id' => $externalId,
                'order_number' => $item['order_number'] ?? $item['number'] ?? null,
                'status' => $item['status'] ?? $item['financial_status'] ?? 'pending',
                'total' => $item['total'] ?? $item['total_price'] ?? 0,
                'subtotal' => $item['subtotal'] ?? $item['subtotal_price'] ?? 0,
                'discount' => $item['discount'] ?? $item['total_discounts'] ?? $item['discount_total'] ?? 0,
                'shipping' => $item['shipping'] ?? $item['total_shipping_price_set']['shop_money']['amount'] ?? $item['shipping_total'] ?? 0,
                'tax' => $item['tax'] ?? $item['total_tax'] ?? 0,
                'currency' => $item['currency'] ?? 'EUR',
                'placed_at' => $placedAt ?? now(),
                'metadata' => $item['metadata'] ?? null,
            ]);

            $this->syncOrderItems($order, $item['items'] ?? $item['line_items'] ?? []);
        }
    }

    private function resolveCustomer(array $item): ?Customer
    {
        // Caso 1: el connector resolvió el email directamente en el normalize.
        $email = strtolower($item['customer_email'] ?? $item['email'] ?? $item['billing']['email'] ?? '');

        // Caso 2: PrestaShop devuelve solo customer_external_id; resolver via tabla.
        $extCustomerId = (string) ($item['customer_external_id'] ?? $item['customer']['id'] ?? $item['customer_id'] ?? '');

        if (! $email && $extCustomerId) {
            $existing = Customer::where('store_id', $this->store->id)
                ->where('external_id', $extCustomerId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if (! $email) {
            return null;
        }

        return Customer::firstOrCreate(
            ['store_id' => $this->store->id, 'email' => $email],
            [
                'email_hash' => hash('sha256', $email),
                'first_name' => $item['billing_address']['first_name'] ?? $item['billing']['first_name'] ?? null,
                'last_name' => $item['billing_address']['last_name'] ?? $item['billing']['last_name'] ?? null,
                'external_id' => $extCustomerId,
                'status' => 'subscribed',
            ]
        );
    }

    private function syncOrderItems(Order $order, array $lineItems): void
    {
        foreach ($lineItems as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'external_product_id' => (string) ($line['external_product_id'] ?? $line['product_id'] ?? ''),
                'title' => $line['title'] ?? $line['name'] ?? '',
                'sku' => $line['sku'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
                'price' => $line['price'] ?? 0,
                'total' => $line['total'] ?? $line['line_total'] ?? (($line['price'] ?? 0) * ($line['quantity'] ?? 1)),
                'image_url' => $line['image_url'] ?? $line['image'] ?? null,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncOrdersJob failed', [
            'store_id' => $this->store->id,
            'since' => $this->since?->toIso8601String(),
            'error' => $exception->getMessage(),
        ]);
    }
}
