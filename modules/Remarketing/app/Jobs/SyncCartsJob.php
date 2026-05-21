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
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;

class SyncCartsJob implements ShouldQueue
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
        $connector = $registry->for($this->store);

        if (! method_exists($connector, 'syncCarts')) {
            Log::warning('SyncCartsJob: connector does not support syncCarts', [
                'store_id' => $this->store->id,
                'platform' => $this->store->platform,
            ]);

            return;
        }

        $connector->syncCarts(
            fn (array $chunk) => $this->upsertCarts($chunk),
            $this->since,
        );
    }

    private function upsertCarts(array $carts): void
    {
        foreach ($carts as $item) {
            $externalId = (string) ($item['external_id'] ?? $item['id'] ?? '');

            if (! $externalId) {
                continue;
            }

            $customer = $this->resolveCustomer($item);

            Cart::updateOrCreate(
                ['store_id' => $this->store->id, 'external_id' => $externalId],
                [
                    'customer_id' => $customer?->id,
                    'email' => $customer?->email ?? $item['email'] ?? null,
                    'items' => $item['items'] ?? [],
                    'total' => $item['total'] ?? 0,
                    'currency' => $item['currency'] ?? 'EUR',
                    'status' => 'abandoned',
                    'abandoned_at' => $item['created_at'] ?? now(),
                    'url' => $item['url'] ?? null,
                ]
            );
        }
    }

    private function resolveCustomer(array $item): ?Customer
    {
        $email = strtolower($item['email'] ?? $item['customer_email'] ?? '');
        $extCustomerId = (string) ($item['customer_external_id'] ?? $item['customer_id'] ?? '');

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
                'first_name' => $item['customer_first_name'] ?? null,
                'last_name' => $item['customer_last_name'] ?? null,
                'external_id' => $extCustomerId,
                'status' => 'subscribed',
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncCartsJob failed', [
            'store_id' => $this->store->id,
            'since' => $this->since?->toIso8601String(),
            'error' => $exception->getMessage(),
        ]);
    }
}
