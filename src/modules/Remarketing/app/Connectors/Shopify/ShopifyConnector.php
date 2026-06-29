<?php

namespace Modules\Remarketing\Connectors\Shopify;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\AbstractConnector;
use Modules\Remarketing\DTOs\EventDTO;

class ShopifyConnector extends AbstractConnector
{
    private const API_VERSION = '2024-01';

    public function platform(): string
    {
        return 'shopify';
    }

    public function authenticate(array $credentials): bool
    {
        $domain = $credentials['domain'] ?? $this->store?->domain;
        $token = $credentials['access_token'] ?? $this->store?->access_token;

        try {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $token])
                ->get($this->baseUrl($domain).'/shop.json');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('ShopifyConnector::authenticate failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $header = $request->header('X-Shopify-Hmac-Sha256');
        $secret = $this->store?->api_secret;

        if (! $header || ! $secret) {
            return false;
        }

        $rawBody = $request->getContent();
        $expected = hash_hmac('sha256', $rawBody, $secret, true);

        return hash_equals($expected, base64_decode($header));
    }

    public function subscribeWebhooks(string $callbackBase): array
    {
        $topics = [
            'orders/create',
            'orders/updated',
            'customers/create',
            'customers/update',
            'customers/email_marketing_consent/update',
            'checkouts/create',
            'checkouts/update',
            'products/update',
            'inventory_levels/update',
        ];

        $createdIds = [];

        foreach ($topics as $topic) {
            try {
                $response = Http::withHeaders(['X-Shopify-Access-Token' => $this->store->access_token])
                    ->post($this->storeBaseUrl().'/webhooks.json', [
                        'webhook' => [
                            'topic' => $topic,
                            'address' => $callbackBase.'/r/webhooks/shopify/'.$this->store->webhook_token,
                            'format' => 'json',
                        ],
                    ]);

                if ($response->successful()) {
                    $createdIds[] = $response->json('webhook.id');
                }
            } catch (\Throwable $e) {
                Log::warning('ShopifyConnector::subscribeWebhooks failed', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $createdIds;
    }

    public function syncCatalog(callable $onChunk): void
    {
        $url = $this->storeBaseUrl().'/products.json?limit=250';

        while ($url) {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $this->store->access_token])
                ->get($url);

            $products = $response->json('products', []);

            if (! empty($products)) {
                $onChunk($products);
            }

            $url = $this->extractNextLink($response->header('Link'));
        }
    }

    public function syncCustomers(callable $onChunk): void
    {
        $url = $this->storeBaseUrl().'/customers.json?limit=250';

        while ($url) {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $this->store->access_token])
                ->get($url);

            $customers = $response->json('customers', []);

            if (! empty($customers)) {
                $onChunk($customers);
            }

            $url = $this->extractNextLink($response->header('Link'));
        }
    }

    public function syncOrders(callable $onChunk, ?Carbon $since = null): void
    {
        $query = 'status=any&limit=250';

        if ($since) {
            $query .= '&created_at_min='.$since->toIso8601String();
        }

        $url = $this->storeBaseUrl().'/orders.json?'.$query;

        while ($url) {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $this->store->access_token])
                ->get($url);

            $orders = $response->json('orders', []);

            if (! empty($orders)) {
                $onChunk($orders);
            }

            $url = $this->extractNextLink($response->header('Link'));
        }
    }

    public function handleWebhook(string $topic, array $payload): EventDTO
    {
        $type = $this->mapTopicToEventType($topic);
        $externalId = (string) ($payload['id'] ?? $payload['token'] ?? '');

        return new EventDTO(
            type: $type,
            externalId: $externalId,
            storeId: $this->store->id,
            properties: $payload,
            occurredAt: isset($payload['created_at'])
                ? Carbon::parse($payload['created_at'])
                : now(),
        );
    }

    private function mapTopicToEventType(string $topic): string
    {
        return match ($topic) {
            'orders/create' => 'placed_order',
            'orders/updated' => 'order_updated',
            'customers/create', 'customers/update' => 'identify',
            'customers/email_marketing_consent/update' => 'consent_updated',
            'checkouts/create', 'checkouts/update' => 'checkout_start',
            'products/update' => 'product_updated',
            'inventory_levels/update' => 'inventory_updated',
            default => $topic,
        };
    }

    private function baseUrl(string $domain): string
    {
        $host = str_contains($domain, '://') ? $domain : 'https://'.$domain;

        return rtrim($host, '/').'/admin/api/'.self::API_VERSION;
    }

    private function storeBaseUrl(): string
    {
        return $this->baseUrl($this->store->domain);
    }

    private function extractNextLink(?string $linkHeader): ?string
    {
        if (! $linkHeader) {
            return null;
        }

        if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
