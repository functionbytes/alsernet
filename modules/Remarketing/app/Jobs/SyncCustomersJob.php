<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\ConnectorRegistry;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;

class SyncCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 60;

    public function __construct(
        public readonly Store $store,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(ConnectorRegistry $registry): void
    {
        $registry->for($this->store)->syncCustomers(
            fn (array $chunk) => $this->upsertCustomers($chunk)
        );
    }

    private function upsertCustomers(array $customers): void
    {
        foreach ($customers as $item) {
            $email = strtolower($item['email'] ?? '');

            if (! $email) {
                continue;
            }

            $tags = $item['tags'] ?? [];
            if (is_string($tags)) {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }

            Customer::updateOrCreate(
                ['store_id' => $this->store->id, 'email' => $email],
                [
                    'email_hash' => hash('sha256', $email),
                    'first_name' => $item['first_name'] ?? $item['firstname'] ?? null,
                    'last_name' => $item['last_name'] ?? $item['lastname'] ?? null,
                    'phone' => $item['phone'] ?? null,
                    'country' => $item['country'] ?? null,
                    'locale' => $item['locale'] ?? null,
                    'external_id' => (string) ($item['external_id'] ?? $item['id'] ?? ''),
                    'tags' => $tags,
                    'status' => $this->mapCustomerStatus($item),
                    'consent_marketing' => $this->resolveConsent($item) ? 1 : 0,
                    'orders_count' => $item['orders_count'] ?? 0,
                ]
            );
        }
    }

    /**
     * Resuelve el flag de consent en formato normalizado (PrestaShop, custom)
     * o formatos crudos de provider (Shopify: accepts_marketing /
     * email_marketing_consent.state, WooCommerce: opted_in).
     */
    private function resolveConsent(array $item): bool
    {
        if (array_key_exists('consent_marketing', $item)) {
            return (bool) $item['consent_marketing'];
        }

        if (array_key_exists('accepts_marketing', $item)) {
            return (bool) $item['accepts_marketing'];
        }

        $emailConsent = $item['email_marketing_consent']['state'] ?? null;

        if ($emailConsent !== null) {
            return $emailConsent === 'subscribed';
        }

        return false;
    }

    private function mapCustomerStatus(array $item): string
    {
        return $this->resolveConsent($item) ? 'subscribed' : 'unsubscribed';
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncCustomersJob failed', [
            'store_id' => $this->store->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
