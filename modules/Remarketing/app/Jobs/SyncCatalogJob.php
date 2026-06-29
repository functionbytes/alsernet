<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\ConnectorRegistry;
use Modules\Remarketing\Models\Product;
use Modules\Remarketing\Models\Store;

class SyncCatalogJob implements ShouldQueue
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
        $registry->for($this->store)->syncCatalog(
            fn (array $chunk) => $this->upsertProducts($chunk)
        );

        $this->store->update(['last_synced_at' => now()]);
    }

    private function upsertProducts(array $products): void
    {
        foreach ($products as $item) {
            $externalId = (string) ($item['external_id'] ?? $item['id'] ?? '');

            if (! $externalId) {
                continue;
            }

            $tags = $item['tags'] ?? [];
            if (is_string($tags)) {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }

            Product::updateOrCreate(
                ['store_id' => $this->store->id, 'external_id' => $externalId],
                [
                    'sku' => $item['sku'] ?? $item['variants'][0]['sku'] ?? null,
                    'title' => $item['title'] ?? $item['name'] ?? '',
                    'description' => $item['description'] ?? $item['body_html'] ?? null,
                    'url' => $item['url'] ?? $item['handle'] ?? $item['permalink'] ?? '',
                    'image_url' => $item['image_url'] ?? $item['image']['src'] ?? $item['images'][0]['src'] ?? null,
                    'price' => $item['price'] ?? $item['variants'][0]['price'] ?? 0,
                    'compare_at_price' => $item['compare_at_price'] ?? $item['variants'][0]['compare_at_price'] ?? null,
                    'currency' => $item['currency'] ?? 'EUR',
                    'inventory' => $item['inventory'] ?? $item['variants'][0]['inventory_quantity'] ?? $item['stock_quantity'] ?? 0,
                    'vendor' => $item['vendor'] ?? null,
                    'tags' => $tags,
                    'collections' => $item['collections'] ?? [],
                    'status' => $item['status'] ?? 'active',
                    'metadata' => $item['metadata'] ?? null,
                    'synced_at' => now(),
                ]
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncCatalogJob failed', [
            'store_id' => $this->store->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
