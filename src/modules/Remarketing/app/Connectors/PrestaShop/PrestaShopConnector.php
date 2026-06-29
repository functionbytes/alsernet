<?php

namespace Modules\Remarketing\Connectors\PrestaShop;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Connectors\AbstractConnector;
use Modules\Remarketing\DTOs\EventDTO;

/**
 * Connector para PrestaShop 1.7 / 8.x.
 *
 * Auth:
 *   - PrestaShop usa basic auth con la API key como username y password vacío.
 *   - La API key se genera en Configuración avanzada → Webservice → Add new webservice key.
 *
 * Webhooks:
 *   - PrestaShop NO tiene webhooks nativos. La ingesta en tiempo real se hace
 *     instalando el módulo `remarketingbridge` en la tienda, que dispara hooks
 *     desde `actionCartSave`, `actionValidateOrder`, etc., firmados HMAC-SHA256.
 *
 * Sync:
 *   - REST sobre /api/{resource}?display=full&output_format=JSON con paginación
 *     `limit=offset,count` y filtros por fecha.
 */
class PrestaShopConnector extends AbstractConnector
{
    public function platform(): string
    {
        return 'prestashop';
    }

    public function authenticate(array $credentials): bool
    {
        $domain = $credentials['domain'] ?? $this->store?->domain;
        $apiKey = $credentials['api_key'] ?? $this->store?->api_key;

        if (! $domain || ! $apiKey) {
            return false;
        }

        // Detecta automáticamente si mod_rewrite está disponible.
        // Si /api/ devuelve 200 con auth → usar /api/. Si no → fallback dispatcher.php.
        $useRewrite = $this->detectRewriteSupport($domain, $apiKey);

        if ($this->store) {
            $settings = (array) ($this->store->settings ?? []);
            $settings['ps_use_rewrite'] = $useRewrite;
            $this->store->settings = $settings;
            $this->store->save();
        }

        return $useRewrite || $this->testDispatcher($domain, $apiKey);
    }

    protected function detectRewriteSupport(string $domain, string $apiKey): bool
    {
        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->acceptJson()
                ->timeout(10)
                ->get($this->endpoint($domain, '/api/'), ['output_format' => 'JSON']);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function testDispatcher(string $domain, string $apiKey): bool
    {
        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->acceptJson()
                ->timeout(10)
                ->get($this->endpoint($domain, '/webservice/dispatcher.php'), [
                    'url' => 'customers',
                    'limit' => '1',
                    'output_format' => 'JSON',
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('PrestaShopConnector::testDispatcher failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Verifica HMAC-SHA256 de los webhooks emitidos por el módulo
     * PrestaShop bridge. Header: X-Remarketing-Signature.
     */
    public function verifyWebhook(Request $request): bool
    {
        $header = $request->header('X-Remarketing-Signature');
        $secret = $this->store?->api_secret;

        if (! $header || ! $secret) {
            return false;
        }

        $rawBody = $request->getContent();
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, $header);
    }

    /**
     * PrestaShop no soporta suscripción remota de webhooks. El módulo
     * `remarketingbridge` instalado en la tienda emite los webhooks.
     * Este método retorna array vacío y deja la responsabilidad al admin.
     */
    public function subscribeWebhooks(string $callbackBase): array
    {
        return [];
    }

    public function syncCatalog(callable $onChunk): void
    {
        $this->paginate('products', 100, function (array $items) use ($onChunk) {
            $hydrated = array_map(fn ($p) => $this->hydrateProduct($p), $items);
            $hydrated = array_filter($hydrated);

            if (! empty($hydrated)) {
                $onChunk($hydrated);
            }
        });
    }

    public function syncCustomers(callable $onChunk): void
    {
        $this->paginate('customers', 100, function (array $items) use ($onChunk) {
            $onChunk(array_map(fn ($c) => $this->normalizeCustomer($c), $items));
        });
    }

    public function syncOrders(callable $onChunk, ?Carbon $since = null): void
    {
        $filters = [];

        if ($since) {
            $filters['date'] = '1';
            $filters['filter[date_add]'] = '>['.$since->toDateTimeString().']';
        }

        $this->paginate('orders', 100, function (array $items) use ($onChunk) {
            $onChunk(array_map(fn ($o) => $this->normalizeOrder($o), $items));
        }, $filters);
    }

    public function syncCarts(callable $onChunk, ?Carbon $since = null): void
    {
        $filters = [];

        if ($since) {
            $filters['date'] = '1';
            $filters['filter[date_add]'] = '>['.$since->toDateTimeString().']';
        }

        $this->paginate('carts', 100, function (array $items) use ($onChunk) {
            $onChunk(array_map(fn ($c) => $this->normalizeCart($c), $items));
        }, $filters);
    }

    public function handleWebhook(string $topic, array $payload): EventDTO
    {
        $type = $this->mapTopicToEventType($topic);
        $externalId = (string) (
            $payload['order_id']
            ?? $payload['cart_id']
            ?? $payload['customer_id']
            ?? $payload['product_id']
            ?? $payload['id']
            ?? ''
        );

        $occurredAt = isset($payload['occurred_at'])
            ? Carbon::parse($payload['occurred_at'])
            : now();

        return new EventDTO(
            type: $type,
            externalId: $externalId,
            storeId: $this->store->id,
            properties: $payload,
            occurredAt: $occurredAt,
        );
    }

    /* ============================================================
     * Internal helpers
     * ============================================================ */

    /**
     * Itera resource paginado por offset usando query `limit=offset,count`
     * y `display=full`. Llama a $onChunk con el array crudo de items.
     */
    protected function paginate(string $resource, int $count, callable $onChunk, array $extraFilters = []): void
    {
        $offset = 0;

        while (true) {
            $useRewrite = $this->shouldUseRewrite();

            $params = array_merge([
                'output_format' => 'JSON',
                'display' => 'full',
                'limit' => $offset.','.$count,
            ], $extraFilters);

            if (! $useRewrite) {
                $params = array_merge(['url' => $resource], $params);
                $url = $this->storeEndpoint('/webservice/dispatcher.php');
            } else {
                $url = $this->storeEndpoint('/api/'.$resource);
            }

            try {
                $response = Http::withBasicAuth($this->store->api_key, '')
                    ->acceptJson()
                    ->timeout(60)
                    ->get($url, $params);
            } catch (\Throwable $e) {
                Log::warning('PrestaShopConnector::paginate request failed', [
                    'resource' => $resource,
                    'offset' => $offset,
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            if (! $response->successful()) {
                Log::warning('PrestaShopConnector::paginate non-2xx', [
                    'resource' => $resource,
                    'status' => $response->status(),
                ]);

                return;
            }

            $items = $response->json($resource, []);

            if (empty($items)) {
                return;
            }

            $onChunk($items);

            if (count($items) < $count) {
                return;
            }

            $offset += $count;
        }
    }

    /**
     * Hidrata un producto PrestaShop al formato esperado por SyncCatalogJob.
     * PrestaShop devuelve nombres y descripciones como objetos multi-idioma:
     *   { "language": [{ "id": 1, "value": "..." }] }
     */
    protected function hydrateProduct(array $p): ?array
    {
        $id = $p['id'] ?? null;

        if (! $id) {
            return null;
        }

        return [
            'external_id' => (string) $id,
            'sku' => $p['reference'] ?? null,
            'title' => $this->extractMultilang($p['name'] ?? ''),
            'description' => $this->extractMultilang($p['description_short'] ?? $p['description'] ?? ''),
            'url' => $this->productUrl($p),
            'image_url' => $this->productImageUrl($id),
            'price' => isset($p['price']) ? (float) $p['price'] : 0,
            'compare_at_price' => null,
            'currency' => $this->defaultCurrency(),
            'inventory' => isset($p['quantity']) ? (int) $p['quantity'] : 0,
            'vendor' => $p['manufacturer_name'] ?? null,
            'tags' => [],
            'collections' => [],
            'status' => ($p['active'] ?? '1') === '1' ? 'active' : 'archived',
            'metadata' => [
                'ps_id_manufacturer' => $p['id_manufacturer'] ?? null,
                'ps_id_category_default' => $p['id_category_default'] ?? null,
                'ps_ean13' => $p['ean13'] ?? null,
                'ps_upc' => $p['upc'] ?? null,
            ],
        ];
    }

    protected function normalizeCustomer(array $c): array
    {
        return [
            'external_id' => (string) ($c['id'] ?? ''),
            'email' => strtolower($c['email'] ?? ''),
            'first_name' => $c['firstname'] ?? null,
            'last_name' => $c['lastname'] ?? null,
            'country' => null,
            'locale' => null,
            'consent_marketing' => isset($c['newsletter']) && $c['newsletter'] === '1',
            'metadata' => [
                'ps_id_default_group' => $c['id_default_group'] ?? null,
                'ps_id_lang' => $c['id_lang'] ?? null,
                'ps_active' => $c['active'] ?? null,
                'ps_optin' => $c['optin'] ?? null,
            ],
        ];
    }

    protected function normalizeOrder(array $o): array
    {
        $items = [];

        $rows = $o['associations']['order_rows'] ?? [];

        if (isset($rows['order_row'])) {
            $rows = $this->ensureList($rows['order_row']);
        } elseif (is_array($rows) && ! array_is_list($rows)) {
            $rows = [$rows];
        }

        if (! is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $items[] = [
                'external_product_id' => (string) ($row['product_id'] ?? ''),
                'title' => $row['product_name'] ?? '',
                'sku' => $row['product_reference'] ?? null,
                'quantity' => (int) ($row['product_quantity'] ?? 1),
                'price' => (float) ($row['unit_price_tax_incl'] ?? $row['product_price'] ?? 0),
                'total' => (float) ($row['unit_price_tax_incl'] ?? 0) * (int) ($row['product_quantity'] ?? 1),
                'image_url' => null,
            ];
        }

        return [
            'external_id' => (string) ($o['id'] ?? ''),
            'order_number' => $o['reference'] ?? null,
            'status' => $this->mapOrderState($o['current_state'] ?? null),
            'total' => (float) ($o['total_paid_tax_incl'] ?? $o['total_paid'] ?? 0),
            'subtotal' => (float) ($o['total_products'] ?? 0),
            'discount' => (float) ($o['total_discounts_tax_incl'] ?? 0),
            'shipping' => (float) ($o['total_shipping_tax_incl'] ?? 0),
            'tax' => (float) ($o['total_paid_tax_incl'] ?? 0) - (float) ($o['total_paid_tax_excl'] ?? 0),
            'currency' => $this->defaultCurrency(),
            'placed_at' => isset($o['date_add']) ? Carbon::parse($o['date_add']) : now(),
            'customer_external_id' => (string) ($o['id_customer'] ?? ''),
            'customer_email' => null, // resolver en el job vía SyncCustomers
            'metadata' => [
                'ps_id_carrier' => $o['id_carrier'] ?? null,
                'ps_payment' => $o['payment'] ?? null,
                'ps_module' => $o['module'] ?? null,
            ],
            'items' => $items,
        ];
    }

    protected function normalizeCart(array $c): array
    {
        $items = [];
        $total = 0;

        $rows = $c['associations']['cart_rows'] ?? [];

        if (isset($rows['cart_row'])) {
            $rows = $this->ensureList($rows['cart_row']);
        } elseif (is_array($rows) && ! array_is_list($rows)) {
            $rows = [$rows];
        }

        if (! is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $qty = (int) ($row['quantity'] ?? 1);
            $price = (float) ($row['unit_price'] ?? 0);
            $lineTotal = $price * $qty;
            $total += $lineTotal;

            $items[] = [
                'external_product_id' => (string) ($row['id_product'] ?? ''),
                'title' => $row['name'] ?? '',
                'sku' => $row['reference'] ?? null,
                'quantity' => $qty,
                'price' => $price,
                'total' => $lineTotal,
                'image_url' => null,
            ];
        }

        return [
            'external_id' => (string) ($c['id'] ?? ''),
            'customer_external_id' => (string) ($c['id_customer'] ?? ''),
            'items' => $items,
            'total' => $total,
            'currency' => $this->defaultCurrency(),
            'created_at' => isset($c['date_add']) ? Carbon::parse($c['date_add']) : now(),
            'metadata' => [
                'ps_id_currency' => $c['id_currency'] ?? null,
                'ps_id_lang' => $c['id_lang'] ?? null,
            ],
        ];
    }

    protected function mapTopicToEventType(string $topic): string
    {
        return match ($topic) {
            'cart.updated', 'cart.saved' => 'add_to_cart',
            'cart.abandoned' => 'cart_abandoned',
            'order.created', 'order.validated' => 'placed_order',
            'order.updated' => 'order_updated',
            'customer.created' => 'identify',
            'customer.updated' => 'customer_updated',
            'product.updated' => 'product_updated',
            default => $topic,
        };
    }

    protected function mapOrderState(mixed $stateId): string
    {
        // PrestaShop default order states (best-effort mapping):
        // 1=Awaiting check, 2=Payment accepted, 3=Processing, 4=Shipped,
        // 5=Delivered, 6=Cancelled, 7=Refunded, 8=Payment error, 9=On backorder, 10=Awaiting bank wire
        return match ((int) $stateId) {
            2, 3, 4, 5 => 'completed',
            6 => 'cancelled',
            7 => 'refunded',
            default => 'pending',
        };
    }

    protected function extractMultilang(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Caso típico: { "language": [{ "id": 1, "value": "..." }] }
            $langs = $value['language'] ?? $value;

            if (isset($langs['value'])) {
                return (string) $langs['value'];
            }

            if (is_array($langs)) {
                $first = reset($langs);

                if (is_array($first) && isset($first['value'])) {
                    return (string) $first['value'];
                }

                if (is_string($first)) {
                    return $first;
                }
            }
        }

        return '';
    }

    protected function productUrl(array $p): string
    {
        $domain = rtrim($this->store->domain, '/');

        if (! str_contains($domain, '://')) {
            $domain = 'https://'.$domain;
        }

        $linkRewrite = $this->extractMultilang($p['link_rewrite'] ?? '');
        $id = $p['id'] ?? '';

        return $linkRewrite
            ? $domain.'/index.php?id_product='.$id.'&controller=product'
            : $domain;
    }

    protected function productImageUrl(string|int $productId): ?string
    {
        // Imagen no expuesta directamente en /api/products. Se podría hacer
        // GET /api/images/products/{id} para obtener IDs y construir la URL.
        // Para MVP: null. v2: implementar fetch de la cover.
        return null;
    }

    protected function defaultCurrency(): string
    {
        return $this->store?->settings['default_currency'] ?? 'EUR';
    }

    protected function storeEndpoint(string $path): string
    {
        return $this->endpoint($this->store->domain, $path);
    }

    /**
     * Indica si la tienda usa mod_rewrite (`/api/customers`) o el fallback
     * (`/webservice/dispatcher.php?url=customers`). El valor se cachea en
     * `store.settings.ps_use_rewrite` durante `authenticate()`.
     */
    protected function shouldUseRewrite(): bool
    {
        $settings = (array) ($this->store?->settings ?? []);

        return (bool) ($settings['ps_use_rewrite'] ?? false);
    }

    protected function endpoint(string $domain, string $path): string
    {
        if (str_contains($domain, '://')) {
            $host = $domain;
        } else {
            // Detecta entornos locales (localhost, 127.0.0.1, *.test, *.local) y usa http://.
            $isLocal = preg_match('/^(localhost|127\.0\.0\.1|.+\.(test|local|localhost))(:\d+)?$/', $domain) === 1;
            $host = ($isLocal ? 'http://' : 'https://').$domain;
        }

        return rtrim($host, '/').'/'.ltrim($path, '/');
    }

    /**
     * PrestaShop devuelve un array asociativo cuando hay un solo elemento
     * y un array indexado cuando hay varios. Este helper normaliza siempre
     * a array indexado.
     */
    protected function ensureList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }
}
