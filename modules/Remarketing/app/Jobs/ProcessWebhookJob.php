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
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Order;
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

        Order::create([
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
