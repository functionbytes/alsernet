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
use Modules\Remarketing\DTOs\EventDTO;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Models\OrderItem;
use Modules\Remarketing\Models\Store;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        public readonly Store $store,
        public readonly string $topic,
        public readonly array $payload,
    ) {
        $this->onQueue('remarketing-webhooks');
    }

    public function handle(ConnectorRegistry $registry): void
    {
        $dto = $registry->for($this->store)->handleWebhook($this->topic, $this->payload);

        Event::create([
            'store_id' => $dto->storeId,
            'customer_id' => null,
            'type' => $dto->type,
            'properties' => $dto->properties,
            'source' => 'webhook',
            'occurred_at' => $dto->occurredAt,
            'received_at' => now(),
        ]);

        match ($dto->type) {
            'placed_order', 'order_updated' => $this->handleOrder($dto),
            'cart_abandoned', 'add_to_cart' => $this->handleCart($dto),
            'identify' => $this->handleIdentify($dto),
            default => null,
        };
    }

    private function handleOrder(EventDTO $dto): void
    {
        $p = $dto->properties;

        $email = $p['email'] ?? $p['billing']['email'] ?? null;

        if (! $email) {
            return;
        }

        $customer = Customer::firstOrCreate(
            ['store_id' => $dto->storeId, 'email' => strtolower($email)],
            [
                'email_hash' => hash('sha256', strtolower($email)),
                'first_name' => $p['billing_address']['first_name'] ?? $p['billing']['first_name'] ?? null,
                'last_name' => $p['billing_address']['last_name'] ?? $p['billing']['last_name'] ?? null,
                'external_id' => (string) ($p['customer']['id'] ?? $p['customer_id'] ?? null),
                'status' => 'subscribed',
            ]
        );

        $externalId = (string) ($p['id'] ?? '');

        if (! $externalId || Order::where('store_id', $dto->storeId)->where('external_id', $externalId)->exists()) {
            return;
        }

        $order = Order::create([
            'store_id' => $dto->storeId,
            'customer_id' => $customer->id,
            'external_id' => $externalId,
            'order_number' => $p['order_number'] ?? $p['number'] ?? null,
            'status' => $p['financial_status'] ?? $p['status'] ?? 'pending',
            'total' => $p['total_price'] ?? $p['total'] ?? 0,
            'subtotal' => $p['subtotal_price'] ?? $p['subtotal'] ?? 0,
            'discount' => $p['total_discounts'] ?? $p['discount_total'] ?? 0,
            'shipping' => $p['total_shipping_price_set']['shop_money']['amount'] ?? $p['shipping_total'] ?? 0,
            'tax' => $p['total_tax'] ?? $p['total_tax'] ?? 0,
            'currency' => $p['currency'] ?? 'EUR',
            'placed_at' => isset($p['created_at']) ? Carbon::parse($p['created_at']) : now(),
            'metadata' => $p,
        ]);

        $this->syncOrderItems($order, $p);
    }

    private function syncOrderItems(Order $order, array $payload): void
    {
        $lines = $payload['items']
            ?? $payload['line_items']
            ?? $payload['order_rows']
            ?? $payload['products']
            ?? [];

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'external_product_id' => (string) ($line['external_product_id'] ?? $line['product_id'] ?? $line['id'] ?? ''),
                'title' => $line['title'] ?? $line['name'] ?? $line['product_name'] ?? '',
                'sku' => $line['sku'] ?? $line['product_reference'] ?? null,
                'quantity' => $line['quantity'] ?? $line['product_quantity'] ?? $line['qty'] ?? 1,
                'price' => $line['price'] ?? $line['unit_price_tax_incl'] ?? $line['product_price'] ?? 0,
                'total' => $line['total'] ?? $line['line_total'] ?? (($line['price'] ?? 0) * ($line['quantity'] ?? 1)),
                'image_url' => $line['image_url'] ?? $line['image'] ?? null,
            ]);
        }
    }

    private function handleCart(EventDTO $dto): void
    {
        $p = $dto->properties;
        $email = $p['email'] ?? $p['customer']['email'] ?? null;
        $externalId = (string) ($p['cart_id'] ?? $p['id'] ?? '');

        if (! $externalId) {
            return;
        }

        $customer = null;
        if ($email) {
            $customer = Customer::firstOrCreate(
                ['store_id' => $dto->storeId, 'email' => strtolower($email)],
                [
                    'email_hash' => hash('sha256', strtolower($email)),
                    'first_name' => $p['customer']['first_name'] ?? null,
                    'last_name' => $p['customer']['last_name'] ?? null,
                    'external_id' => (string) ($p['customer']['id'] ?? $p['customer_id'] ?? null),
                    'status' => 'subscribed',
                ]
            );
        }

        $items = $p['items'] ?? $p['products'] ?? $p['cart_items'] ?? [];
        $total = $p['total'] ?? $p['cart_total'] ?? $p['total_price'] ?? 0;

        Cart::updateOrCreate(
            ['store_id' => $dto->storeId, 'external_id' => $externalId],
            [
                'customer_id' => $customer?->id,
                'email' => $email ? strtolower($email) : null,
                'items' => $items,
                'total' => $total,
                'currency' => $p['currency'] ?? 'EUR',
                'status' => 'abandoned',
                'abandoned_at' => $dto->occurredAt,
                'url' => $p['url'] ?? $p['checkout_url'] ?? null,
            ]
        );
    }

    private function handleIdentify(EventDTO $dto): void
    {
        $p = $dto->properties;
        $email = $p['email'] ?? null;

        if (! $email) {
            return;
        }

        $email = strtolower($email);

        Customer::updateOrCreate(
            ['store_id' => $dto->storeId, 'email' => $email],
            [
                'email_hash' => hash('sha256', $email),
                'first_name' => $p['first_name'] ?? null,
                'last_name' => $p['last_name'] ?? null,
                'external_id' => (string) ($p['id'] ?? ''),
                'status' => 'subscribed',
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWebhookJob failed', [
            'store_id' => $this->store->id,
            'topic' => $this->topic,
            'error' => $exception->getMessage(),
        ]);
    }
}
