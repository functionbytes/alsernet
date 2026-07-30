<?php

namespace Modules\HelpdeskPrestashop\Services;

use App\Helpers\PiiMasker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Concerns\HasCircuitBreaker;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Jobs\RefreshPsContextJob;
use Modules\HelpdeskPrestashop\Support\HmacSigner;

class PrestashopContextService
{
    use HasCircuitBreaker;

    private const EMPTY_CONTEXT = [
        'customer' => ['found' => false],
        'orders' => [],
        'carts' => [],
    ];

    private const CIRCUIT_KEY = 'helpdeskprestashop:circuit_failures';

    private const CIRCUIT_CONFIG_PREFIX = 'helpdeskprestashop';

    public function getCustomerContext(string $email): array
    {
        $email = $this->normalizeEmail($email);
        $key = $this->cacheKey($email);
        $cached = Cache::get($key);

        if ($cached !== null) {
            $this->maybeRevalidate($email, $cached);

            unset($cached['_cached_at'], $cached['_ttl']);

            return $cached;
        }

        try {
            $result = $this->fetchContext($email);
        } catch (PsUpstreamException $e) {
            Log::warning('HelpdeskPrestashop: upstream no disponible, se devuelve contexto vacío.', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);

            return self::EMPTY_CONTEXT;
        }

        if ($result === null) {
            return self::EMPTY_CONTEXT;
        }

        $this->putInCache($key, $result);

        return $result;
    }

    /**
     * Refresca la caché del cliente con un único Cache::put atómico.
     * Si la API falla, NO toca la caché (mantiene la entrada anterior si existe).
     */
    public function revalidate(string $email): void
    {
        $email = $this->normalizeEmail($email);

        try {
            $result = $this->fetchContext($email);
        } catch (PsUpstreamException) {
            // Si el upstream no está disponible, mantener el dato en caché tal como está.
            return;
        }

        if ($result === null) {
            return;
        }

        $this->putInCache($this->cacheKey($email), $result);
    }

    public function forgetCache(string $email): void
    {
        Cache::forget($this->cacheKey($this->normalizeEmail($email)));
    }

    public function getOrderDetail(int $orderId, ?string $customerEmail = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.detail', $orderId)) {
            return null;
        }

        return $this->callApi('order.detail', [
            'order_id' => $orderId,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ]);
    }

    /**
     * Lista paginada de pedidos del cliente vía la accion dedicada `customer.orders`.
     * Es la fuente fiable de pedidos: `customer.helpdesk_context` puede devolver el
     * array `orders` vacio para algunos clientes aunque `orders_count` sea > 0.
     * El cliente se resuelve por email o, si se da, por id_customer de PrestaShop.
     *
     * @return array<string, mixed>|null
     */
    public function getCustomerOrders(string $email, ?int $prestashopCustomerId = null, int $limit = 10, int $page = 1): ?array
    {
        $lookup = ['email' => $this->normalizeEmail($email)];

        if ($prestashopCustomerId !== null) {
            $lookup['external_id'] = $prestashopCustomerId;
        }

        return $this->callApi('customer.orders', [
            'lookup' => $lookup,
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    public function startOrderReturn(int $orderId, array $items, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.start_return', $orderId)) {
            return null;
        }

        return $this->callApi('order.start_return', [
            'order_id' => $orderId,
            'items' => $items,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ], $idempotencyKey);
    }

    /**
     * Cambia el estado de un pedido de PrestaShop (via OrderHistory en el bridge:
     * respeta stock/facturas y, solo si $notify, el correo del estado). $stateId
     * es el id_order_state real de PrestaShop.
     *
     * @return array{order_id:int,state_id:int,state_name:string,notified:bool,changed:bool}|null
     */
    public function changeOrderStatus(int $orderId, int $stateId, bool $notify = false, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.change_status', $orderId)) {
            return null;
        }

        return $this->callApi('order.change_status', [
            'order_id' => $orderId,
            'state_id' => $stateId,
            'notify' => $notify,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ], $idempotencyKey);
    }

    /**
     * Metadatos de documentos del pedido (facturas / albaranes): número + fecha.
     *
     * @return array{invoices:array<int,array>,delivery_slips:array<int,array>}|null
     */
    public function getOrderDocuments(int $orderId, ?string $customerEmail = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.documents', $orderId)) {
            return null;
        }

        return $this->callApi('order.documents', [
            'order_id' => $orderId,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ]);
    }

    /**
     * Fail-closed: las acciones por-pedido NUNCA van al bridge sin lookup.email.
     * Sin él, el bridge resolvería el pedido solo por su id secuencial, sin
     * verificar que pertenezca al cliente (IDOR). La propiedad pedido↔email la
     * aplica el bridge; aquí garantizamos que siempre tenga con qué aplicarla.
     *
     * @phpstan-assert-if-true string $customerEmail
     */
    private function assertOwnershipEmail(?string $customerEmail, string $action, int $orderId): bool
    {
        if ($customerEmail !== null && trim($customerEmail) !== '') {
            return true;
        }

        Log::warning('PrestashopContextService: llamada por-pedido bloqueada sin email de cliente', [
            'action' => $action,
            'order_id' => $orderId,
        ]);

        return false;
    }

    /**
     * Cambia la dirección (envío/facturación) del pedido a una dirección
     * existente del mismo cliente.
     *
     * @return array{order_id:int,address_id:int,type:string}|null
     */
    public function setOrderAddress(int $orderId, int $addressId, string $type = 'delivery', ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.set_address', $orderId)) {
            return null;
        }

        return $this->callApi('order.set_address', [
            'order_id' => $orderId,
            'address_id' => $addressId,
            'type' => $type,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ], $idempotencyKey);
    }

    /**
     * Reenvía un correo estándar del pedido al cliente (whitelist de tipos).
     *
     * @return array{order_id:int,type:string,sent:bool,to:string}|null
     */
    public function sendOrderEmail(int $orderId, string $type, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.send_email', $orderId)) {
            return null;
        }

        return $this->callApi('order.send_email', [
            'order_id' => $orderId,
            'type' => $type,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ], $idempotencyKey);
    }

    /**
     * Añade una nota interna a un pedido de PrestaShop (visible en el back
     * office). Usa la acción order.add_note del bridge; verifica propiedad por
     * el email del cliente igual que el resto de acciones de pedido.
     *
     * @return array{note_id:int|null,order_id:int,created_at:string,content:string}|null
     */
    public function addOrderNote(int $orderId, string $note, string $agentName, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.add_note', $orderId)) {
            return null;
        }

        return $this->callApi('order.add_note', [
            'order_id' => $orderId,
            'note' => $note,
            'agent_name' => $agentName,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ], $idempotencyKey);
    }

    /**
     * Catálogo de estados de pedido de PrestaShop (id, name, color + flags) para
     * el desplegable de "Cambiar estado" del workspace. Cacheado 1h (cambian raras
     * veces). El id es el `id_order_state` real que espera changeOrderStatus().
     *
     * @return array<int, array{id:int,name:string,color:string,paid:int,shipped:int,delivery:int}>
     */
    public function getOrderStates(): array
    {
        return Cache::remember('ps_order_states', 3600, function (): array {
            try {
                $result = $this->callApi('order.states', []);
            } catch (\Throwable) {
                return [];
            }

            return $result['states'] ?? [];
        });
    }

    /**
     * Asigna número de seguimiento (y opcionalmente transportista) a un pedido.
     * No envía correo por sí mismo — el aviso de envío se dispara al pasar el
     * pedido a "Enviado" con changeOrderStatus($notify: true).
     *
     * @return array{order_id:int,tracking_number:string,carrier_id:int}|null
     */
    public function setOrderTracking(int $orderId, string $trackingNumber, ?int $carrierId = null, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        if (! $this->assertOwnershipEmail($customerEmail, 'order.set_tracking', $orderId)) {
            return null;
        }

        $payload = [
            'order_id' => $orderId,
            'tracking_number' => $trackingNumber,
            'lookup' => ['email' => $this->normalizeEmail($customerEmail)],
        ];

        if ($carrierId !== null) {
            $payload['carrier_id'] = $carrierId;
        }

        return $this->callApi('order.set_tracking', $payload, $idempotencyKey);
    }

    public function testConnection(): array
    {
        $start = microtime(true);

        try {
            $result = $this->callApi('customer.helpdesk_context', ['lookup' => ['email' => '__healthcheck__@invalid.local']]);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            return [
                'ok' => $result !== null,
                'latency_ms' => $latencyMs,
                'error' => $result === null ? 'API no respondió correctamente.' : null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'latency_ms' => (int) ((microtime(true) - $start) * 1000),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchContext(string $email): ?array
    {
        // El `helpdesk_context` falla (HTTP 500) para ciertos clientes del bridge.
        // No dejamos que eso aborte el contexto: capturamos y seguimos con el
        // fallback fiable basado en `customer.orders`.
        try {
            $context = $this->callApi('customer.helpdesk_context', ['lookup' => ['email' => $email]]);
        } catch (PsUpstreamException) {
            $context = null;
        }

        $found = is_array($context) && ($context['customer']['found'] ?? false);

        // Fallback robusto: el `helpdesk_context` del bridge falla (HTTP 500) o
        // devuelve `found=false` para ciertos clientes. La accion `customer.orders`
        // es fiable, asi que construimos/completamos el contexto con ella para que
        // los pedidos aparezcan siempre en la conversacion.
        if (! $found) {
            $list = $this->fetchOrdersList($email);

            if (empty($list)) {
                return $context;
            }

            return [
                'customer' => [
                    'found' => true,
                    'email' => $email,
                    'orders_count' => count($list),
                ],
                'orders' => array_map(fn ($o) => $this->mapOrder($o), $list),
                'carts' => [],
            ];
        }

        // `helpdesk_context` encontro al cliente pero devolvio `orders` vacio
        // aunque tenga pedidos: completamos con `customer.orders`.
        if (empty($context['orders']) && (int) ($context['customer']['orders_count'] ?? 0) > 0) {
            $list = $this->fetchOrdersList($email);

            if (! empty($list)) {
                $context['orders'] = array_map(fn ($o) => $this->mapOrder($o), $list);
            }
        }

        return $context;
    }

    /**
     * Lista cruda de pedidos del cliente vía `customer.orders` (tolerante a fallos).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchOrdersList(string $email): array
    {
        try {
            $orders = $this->getCustomerOrders($email, null, 10, 1);
        } catch (\Throwable) {
            return [];
        }

        return $orders['orders'] ?? ($orders['data'] ?? []);
    }

    /**
     * Normaliza un pedido de `customer.orders` a la forma que consume el panel
     * de contexto (compatible con la salida de `helpdesk_context`).
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function mapOrder(array $o): array
    {
        return [
            'id' => $o['id'] ?? null,
            'reference' => $o['reference'] ?? null,
            'placed_at' => $o['created_at'] ?? ($o['placed_at'] ?? null),
            'payment_method' => $o['payment'] ?? null,
            'currency_sign' => $o['currency'] ?? '€',
            'state' => [
                'name' => $o['state_name'] ?? null,
                'color' => $o['state_color'] ?? null,
            ],
            'totals' => [
                'total' => $o['total'] ?? null,
                'products' => $o['subtotal'] ?? null,
                'shipping' => $o['shipping'] ?? null,
                'discount' => $o['discount'] ?? null,
            ],
            'lines' => [],
        ];
    }

    /**
     * Dispara revalidación en background si el entry está cerca de expirar.
     */
    private function maybeRevalidate(string $email, array $cached): void
    {
        $age = time() - ($cached['_cached_at'] ?? 0);
        $effectiveTtl = $cached['_ttl'] ?? (int) config('helpdeskprestashop.cache_ttl', 300);
        $staleGrace = (int) config('helpdeskprestashop.stale_grace', 30);

        if ($age >= ($effectiveTtl - $staleGrace)) {
            RefreshPsContextJob::dispatch($email)->afterCommit();
        }
    }

    private function putInCache(string $key, array $result): void
    {
        $ttl = $this->ttlFor($result);

        if ($ttl <= 0) {
            return;
        }

        $payload = $result;
        $payload['_cached_at'] = time();
        $payload['_ttl'] = $ttl;

        Cache::put($key, $payload, $ttl);
    }

    private function ttlFor(array $result): int
    {
        if (! ($result['customer']['found'] ?? false)) {
            return (int) config('helpdeskprestashop.miss_ttl', 60);
        }

        return (int) config('helpdeskprestashop.cache_ttl', 300);
    }

    private function cacheKey(string $email): string
    {
        return 'prestashop_ctx_'.md5($email);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Central HTTP caller: builds the HMAC signature (with timestamp) and dispatches the request.
     * Write actions accept an optional idempotency key; one is auto-generated when not provided.
     *
     * Returns the decoded JSON body on success.
     * Returns null when the API responds with ok=false (semantic error: not-found, validation, etc.).
     *
     * @throws PsUpstreamException when the request cannot reach PrestaShop due to network errors,
     *                             HTTP 5xx responses, or an open circuit breaker.
     */
    private function callApi(string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        $apiUrl = (string) config('helpdeskprestashop.api_url', '');
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');

        if ($apiUrl === '' || $secret === '') {
            Log::info('HelpdeskPrestashop: API URL o secreto no configurados — se devuelve null.', [
                'action' => $action,
            ]);

            return null;
        }

        if ($this->isCircuitOpen()) {
            Log::warning('HelpdeskPrestashop: circuit breaker abierto — request omitido.', [
                'action' => $action,
            ]);

            throw new PsUpstreamException('Servicio PrestaShop temporalmente no disponible (circuit breaker).');
        }

        $bodyJson = json_encode(array_merge(['action' => $action], $payload));
        $timestamp = time();
        $signature = HmacSigner::sign($secret, $timestamp, $bodyJson);

        $writeActions = ['customer.add_message', 'order.add_note', 'order.start_return'];
        $headers = [
            'X-Alsernet-Signature' => $signature,
            'X-Alsernet-Timestamp' => (string) $timestamp,
            'X-Alsernet-Action' => $action,
            'Content-Type' => 'application/json',
        ];

        if (in_array($action, $writeActions, true)) {
            $headers['X-Alsernet-Idempotency-Key'] = $idempotencyKey ?? (string) Str::uuid();
        }

        $requestId = request()?->header('X-Request-Id');
        if ($requestId) {
            $headers['X-Request-Id'] = $requestId;
        }

        try {
            $response = Http::connectTimeout((int) config('helpdeskprestashop.http_connect_timeout', 2))
                ->timeout((int) config('helpdeskprestashop.http_timeout', 10))
                ->withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($apiUrl);

            if (! $response->successful()) {
                Log::warning('HelpdeskPrestashop: respuesta no exitosa del upstream.', [
                    'action' => $action,
                    'status' => $response->status(),
                ]);
                $this->recordFailure();

                throw new PsUpstreamException(
                    "El servidor PrestaShop respondió con HTTP {$response->status()}."
                );
            }

            $decoded = $response->json();

            if (! ($decoded['ok'] ?? false)) {
                Log::warning('HelpdeskPrestashop: API devolvió ok=false.', [
                    'action' => $action,
                    'error' => $decoded['error'] ?? 'unknown',
                ]);
                // ok=false con HTTP 200 = error semántico (no-found, validation, etc.),
                // no es un fallo de red. No abre el circuit breaker.

                return null;
            }

            $this->recordSuccess();

            return $decoded['data'] ?? null;
        } catch (PsUpstreamException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop: error de red al llamar al API.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            $this->recordFailure();

            throw new PsUpstreamException(
                'Error de red al contactar PrestaShop: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Returns PS shipping addresses for a customer by email.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerAddresses(string $email): array
    {
        if ($email === '') {
            return [];
        }

        try {
            $data = $this->callApi('customer.addresses', ['lookup' => ['email' => $this->normalizeEmail($email)]]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['addresses'] ?? [];
    }

    /**
     * Returns available PS categories for the search filter dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(?string $lang = null): array
    {
        // Version-scoped key: a catalog change (price drop / back in stock) bumps
        // the version via forgetCatalogCache(), orphaning every lang variant at
        // once instead of trying to enumerate and forget each language key.
        $cacheKey = 'ps.categories.v'.$this->catalogCacheVersion().'.'.($lang ?? 'default');

        return Cache::remember($cacheKey, 3600, function () use ($lang): array {
            $payload = [];

            if ($lang !== null) {
                $payload['lang'] = $lang;
            }

            try {
                $data = $this->callApi('product.categories', $payload);
            } catch (PsUpstreamException) {
                return [];
            }

            return $data['categories'] ?? [];
        });
    }

    private function catalogCacheVersion(): int
    {
        return (int) Cache::rememberForever('ps.catalog.version', fn (): int => 1);
    }

    /**
     * Invalidate all cached catalog data (categories in every language) by
     * bumping the catalog cache version. Called from a listener on PrestaShop
     * price-drop / back-in-stock events.
     */
    public function forgetCatalogCache(): void
    {
        Cache::forever('ps.catalog.version', $this->catalogCacheVersion() + 1);
    }

    /**
     * Busca productos en PrestaShop via el bridge por texto.
     * Devuelve array de productos normalizados (vacío si el bridge no los soporta o falla).
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(string $query, int $limit = 10, ?string $lang = null, int $offset = 0, bool $inStockOnly = false): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $payload = ['query' => trim($query), 'limit' => $limit, 'offset' => $offset];

        if ($lang !== null) {
            $payload['lang'] = $lang;
        }

        if ($inStockOnly) {
            $payload['in_stock'] = true;
        }

        try {
            $result = $this->callApi('product.search', $payload);
        } catch (PsUpstreamException) {
            return [];
        }

        if (! is_array($result)) {
            return [];
        }

        $products = $result['products'] ?? $result;

        if (! is_array($products)) {
            return [];
        }

        return array_map(fn ($p) => $this->normalizeProduct($p), $products);
    }

    /**
     * Busca un producto específico en PrestaShop por su ID numérico.
     * Devuelve null si el bridge no lo encuentra o falla.
     *
     * @return array<string, mixed>|null
     */
    public function getProductById(int $id, ?string $lang = null): ?array
    {
        return $this->fetchProductByKey('product_id', $id, $lang);
    }

    /**
     * Busca un producto específico en PrestaShop por su referencia/SKU (ej. C112972).
     * Devuelve null si el bridge no lo encuentra o falla.
     *
     * @return array<string, mixed>|null
     */
    public function getProductByReference(string $reference, ?string $lang = null): ?array
    {
        return $this->fetchProductByKey('reference', $reference, $lang);
    }

    /**
     * Núcleo compartido para búsqueda exacta de producto por un campo concreto.
     *
     * @return array<string, mixed>|null
     */
    private function fetchProductByKey(string $key, mixed $value, ?string $lang): ?array
    {
        $payload = [$key => $value];

        if ($lang !== null) {
            $payload['lang'] = $lang;
        }

        try {
            $result = $this->callApi('product.get', $payload);
        } catch (PsUpstreamException) {
            return null;
        }

        if (! is_array($result)) {
            return null;
        }

        $product = $result['product'] ?? $result;

        if (! is_array($product) || empty($product)) {
            return null;
        }

        return $this->normalizeProduct($product);
    }

    /**
     * Extrae productos únicos del historial de pedidos del cliente.
     * Útil para sugerir recompras.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductsFromHistory(string $email, int $limit = 6): array
    {
        $context = $this->getCustomerContext($email);
        $orders = $context['orders'] ?? [];
        $seen = [];
        $products = [];

        foreach ($orders as $order) {
            $lines = $order['lines'] ?? $order['products'] ?? [];

            foreach ($lines as $line) {
                $key = (string) ($line['id_product'] ?? $line['product_id'] ?? $line['name'] ?? '');

                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $products[] = [
                    'id' => $line['id_product'] ?? $line['product_id'] ?? null,
                    'name' => $line['name'] ?? $line['product_name'] ?? '',
                    'sku' => $line['reference'] ?? $line['sku'] ?? null,
                    'price' => (float) ($line['unit_price'] ?? $line['price'] ?? 0),
                    'image' => $line['image_url'] ?? $line['image'] ?? null,
                    'url' => $line['url'] ?? null,
                    'from_history' => true,
                ];

                if (count($products) >= $limit) {
                    break 2;
                }
            }
        }

        return $products;
    }

    /**
     * Normaliza un producto del bridge al formato esperado por el frontend.
     *
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function normalizeProduct(array $p): array
    {
        $price = (float) ($p['price'] ?? $p['unit_price'] ?? 0);
        $taxRate = (float) ($p['tax_rate'] ?? 0);

        return [
            'id' => $p['id'] ?? $p['id_product'] ?? null,
            'name' => $p['name'] ?? '',
            'sku' => $p['reference'] ?? $p['sku'] ?? null,
            'price' => $price,
            'tax_rate' => $taxRate,
            'price_with_tax' => isset($p['price_with_tax'])
                ? (float) $p['price_with_tax']
                : ($taxRate > 0 ? round($price * (1 + $taxRate / 100), 2) : $price),
            'price_original' => isset($p['price_original']) && $p['price_original'] !== null
                ? (float) $p['price_original']
                : null,
            'has_discount' => (bool) ($p['has_discount'] ?? false),
            'stock' => isset($p['stock']) ? (int) $p['stock'] : null,
            'in_stock' => isset($p['in_stock']) ? (bool) $p['in_stock'] : null,
            'brand' => $p['brand'] ?? null,
            'category' => $p['category'] ?? null,
            'description' => $p['description'] ?? null,
            'ean13' => $p['ean13'] ?? null,
            'image' => $p['image_url'] ?? $p['image'] ?? null,
            'url' => $p['url'] ?? null,
        ];
    }
}
