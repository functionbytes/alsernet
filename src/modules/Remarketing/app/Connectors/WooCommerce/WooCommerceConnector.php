<?php

namespace Modules\Remarketing\Connectors\WooCommerce;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\AbstractConnector;
use Modules\Remarketing\DTOs\EventDTO;

class WooCommerceConnector extends AbstractConnector
{
    public function platform(): string
    {
        return 'woocommerce';
    }

    public function authenticate(array $credentials): bool
    {
        $domain = $credentials['domain'] ?? $this->store?->domain;
        $key = $credentials['api_key'] ?? $this->store?->api_key;
        $secret = $credentials['api_secret'] ?? $this->store?->api_secret;

        try {
            $response = Http::withBasicAuth($key, $secret)
                ->get($this->baseUrl($domain).'/system_status');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('WooCommerceConnector::authenticate failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $header = $request->header('X-WC-Webhook-Signature');
        $secret = $this->store?->api_secret;

        if (! $header || ! $secret) {
            return false;
        }

        $rawBody = $request->getContent();
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, $header);
    }

    public function subscribeWebhooks(string $callbackBase): array
    {
        $topics = [
            'order.created',
            'order.updated',
            'customer.created',
            'customer.updated',
            'product.updated',
        ];

        $createdIds = [];
        $deliveryUrl = $callbackBase.'/r/webhooks/woocommerce/'.$this->store->webhook_token;

        foreach ($topics as $topic) {
            try {
                $response = Http::withBasicAuth($this->store->api_key, $this->store->api_secret)
                    ->post($this->storeBaseUrl().'/webhooks', [
                        'name' => 'Remarketing '.$topic,
                        'topic' => $topic,
                        'delivery_url' => $deliveryUrl,
                        'status' => 'active',
                    ]);

                if ($response->successful()) {
                    $createdIds[] = $response->json('id');
                }
            } catch (\Throwable $e) {
                Log::warning('WooCommerceConnector::subscribeWebhooks failed', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $createdIds;
    }

    public function syncCatalog(callable $onChunk): void
    {
        $page = 1;

        do {
            $response = Http::withBasicAuth($this->store->api_key, $this->store->api_secret)
                ->get($this->storeBaseUrl().'/products', ['page' => $page, 'per_page' => 100]);

            $products = $response->json() ?? [];

            if (! empty($products)) {
                $onChunk($products);
            }

            $page++;
        } while (count($products) === 100);
    }

    public function syncCustomers(callable $onChunk): void
    {
        $page = 1;

        do {
            $response = Http::withBasicAuth($this->store->api_key, $this->store->api_secret)
                ->get($this->storeBaseUrl().'/customers', ['page' => $page, 'per_page' => 100]);

            $customers = $response->json() ?? [];

            if (! empty($customers)) {
                $onChunk($customers);
            }

            $page++;
        } while (count($customers) === 100);
    }

    public function syncOrders(callable $onChunk, ?Carbon $since = null): void
    {
        $page = 1;
        $params = ['per_page' => 100];

        if ($since) {
            $params['after'] = $since->toIso8601String();
        }

        do {
            $response = Http::withBasicAuth($this->store->api_key, $this->store->api_secret)
                ->get($this->storeBaseUrl().'/orders', array_merge($params, ['page' => $page]));

            $orders = $response->json() ?? [];

            if (! empty($orders)) {
                $onChunk($orders);
            }

            $page++;
        } while (count($orders) === 100);
    }

    public function handleWebhook(string $topic, array $payload): EventDTO
    {
        $type = $this->mapTopicToEventType($topic);
        $externalId = (string) ($payload['id'] ?? '');

        return new EventDTO(
            type: $type,
            externalId: $externalId,
            storeId: $this->store->id,
            properties: $payload,
            occurredAt: isset($payload['date_created'])
                ? Carbon::parse($payload['date_created'])
                : now(),
        );
    }

    private function mapTopicToEventType(string $topic): string
    {
        return match ($topic) {
            'order.created' => 'placed_order',
            'order.updated' => 'order_updated',
            'customer.created', 'customer.updated' => 'identify',
            'product.updated' => 'product_updated',
            'cart.updated' => 'add_to_cart',
            'cart.converted' => 'purchase',
            default => $topic,
        };
    }

    private function baseUrl(string $domain): string
    {
        $host = str_contains($domain, '://') ? $domain : 'https://'.$domain;

        return rtrim($host, '/').'/wp-json/wc/v3';
    }

    private function storeBaseUrl(): string
    {
        return $this->baseUrl($this->store->domain);
    }
}
