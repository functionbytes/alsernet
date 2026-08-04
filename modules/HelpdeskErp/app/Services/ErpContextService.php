<?php

namespace Modules\HelpdeskErp\Services;

use App\Helpers\PiiMasker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Pulse;
use Modules\Erp\Services\ErpCustomerDataService;
use Modules\Helpdesk\Concerns\HasCircuitBreaker;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\PhoneNormalizerService;
use Modules\HelpdeskErp\Jobs\RefreshErpContextJob;

class ErpContextService
{
    use HasCircuitBreaker;

    /**
     * Obtiene el contexto ERP del cliente buscando primero por email y,
     * si no se encuentra, por teléfono (fallback).
     *
     * Cuando se proporciona $helpdeskCustomerId y el cliente es encontrado,
     * persiste el link en helpdesk_customer_external_ids para búsquedas futuras.
     */
    public function getCustomerContext(string $email, ?string $phone = null, ?int $helpdeskCustomerId = null): array
    {
        $start = microtime(true);
        // Sin email (cliente encontrado solo por teléfono) cacheKey('') sería
        // la misma clave para cualquier cliente sin email — el segundo
        // recibiría el contexto cacheado del primero. Se incorpora el
        // teléfono a la clave en ese caso.
        $key = $this->cacheKey($email ?: 'phone:'.$phone);
        $cached = Cache::get($key);

        if ($cached !== null) {
            $this->maybeScheduleRefresh($email, $cached);
            $result = $this->stripMeta($cached);
            $this->recordPulse($email, $start, cached: true, found: $result['customer']['found'] ?? false);

            return $result;
        }

        $result = $this->fetchFromErp($email, $phone);
        $this->putInCache($key, $result);

        if ($helpdeskCustomerId !== null && ($result['customer']['found'] ?? false)) {
            $this->persistErpLink($helpdeskCustomerId, (int) $result['customer']['id']);
        }

        $this->recordPulse($email, $start, cached: false, found: $result['customer']['found'] ?? false);

        return $result;
    }

    public function forgetCache(string $email): void
    {
        Cache::forget($this->cacheKey($email));
    }

    /**
     * Search customers by email, phone, or NIF.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCustomers(string $query, string $type = 'email'): array
    {
        if (! $this->baseUrl() || strlen($query) < 3) {
            return [];
        }

        if ($this->isCircuitOpen()) {
            return [];
        }

        // Para búsqueda por teléfono, normalizar a dígitos puros para que el manager
        // Oracle detecte correctamente la búsqueda vía heurística ctype_digit().
        if ($type === 'phone') {
            $query = app(PhoneNormalizerService::class)->toDigits($query) ?? $query;
        }

        $params = ['q' => $query, 'limit' => 20, 'type' => $type];

        try {
            $resp = $this->http()->get($this->url('erp/customer/search'), $params);
        } catch (\Throwable $e) {
            $this->recordFailure();

            Log::warning('HelpdeskErp: searchCustomers falló contra el manager.', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Igual que la respuesta no-2xx de abajo: un fallo de conexión tampoco
            // es "sin resultados" — se propaga para que el driver reporte
            // platform_error=true en vez de dar a entender que el cliente no existe.
            throw new \RuntimeException('ERP searchCustomers no pudo conectar con el manager.', previous: $e);
        }

        // Una respuesta no exitosa (401/404/5xx, p. ej. por una URL de manager
        // mal configurada) NO es "sin resultados": se propaga como excepcion
        // para que el driver (ErpIntegrationDriver::search()) la distinga y
        // reporte platform_error=true en vez de una lista vacia silenciosa.
        if (! $resp->successful()) {
            $this->recordFailure();

            Log::warning('HelpdeskErp: searchCustomers recibió una respuesta no exitosa del manager.', [
                'type' => $type,
                'status' => $resp->status(),
            ]);

            throw new \RuntimeException("ERP searchCustomers respondió con status {$resp->status()}.");
        }

        $this->recordSuccess();

        return $resp->json('data') ?? [];
    }

    public function getOrderDetail(int $customerId, int $orderId): ?array
    {
        // Short cache: order detail is re-read on every panel/tab render but barely
        // changes within a session. Cache::remember does not persist null, so failed
        // lookups are naturally retried on the next call instead of being cached.
        return Cache::remember(
            "helpdeskerp:order_detail:{$customerId}:{$orderId}",
            now()->addSeconds(60),
            fn (): ?array => $this->fetchOrderDetail($customerId, $orderId)
        );
    }

    private function fetchOrderDetail(int $customerId, int $orderId): ?array
    {
        if (class_exists(ErpCustomerDataService::class) && extension_loaded('oci8')) {
            try {
                return app(ErpCustomerDataService::class)->getOrderDetail($orderId, $customerId);
            } catch (\Throwable $e) {
                Log::warning('HelpdeskErp: error en detalle de pedido directo Oracle.', [
                    'customer_id' => $customerId,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        if (! $this->baseUrl()) {
            return null;
        }

        if ($this->isCircuitOpen()) {
            return null;
        }

        try {
            // Retry transient blips (connection reset, 5xx) with a small backoff;
            // throw:false keeps the existing successful() check in control.
            $resp = $this->http()
                ->retry(2, 200, throw: false)
                ->get($this->url("erp/customer/{$customerId}/orders/{$orderId}"));

            $this->recordSuccess();

            if (! $resp->successful()) {
                return null;
            }

            return $resp->json('data') ?? null;
        } catch (\Throwable $e) {
            $this->recordFailure();

            Log::warning('HelpdeskErp: error al obtener detalle de pedido.', [
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{status: string, manager_url: string, oracle_reachable: bool, latency_ms: int|null}
     */
    public function healthCheck(): array
    {
        $managerUrl = $this->baseUrl();

        if (! $managerUrl) {
            return [
                'status' => 'degraded',
                'manager_url' => '',
                'oracle_reachable' => false,
                'latency_ms' => null,
            ];
        }

        $start = hrtime(true);

        try {
            $resp = $this->http()
                ->timeout(3)
                ->get($this->url('erp/customer/search'), ['q' => '__healthcheck__', 'limit' => 1]);

            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $reachable = $resp->status() < 500;

            return [
                'status' => $reachable ? 'ok' : 'degraded',
                'manager_url' => $managerUrl,
                'oracle_reachable' => $reachable,
                'latency_ms' => $latencyMs,
            ];
        } catch (\Throwable $e) {
            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

            Log::warning('HelpdeskErp: health check fallido.', ['error' => $e->getMessage()]);

            return [
                'status' => 'degraded',
                'manager_url' => $managerUrl,
                'oracle_reachable' => false,
                'latency_ms' => $latencyMs,
            ];
        }
    }

    /* ── Private helpers ──────────────────────────────────────────────────── */

    private function fetchFromErp(string $email, ?string $phone = null): array
    {
        // Cuando el módulo Erp está en la misma app, usar ErpCustomerDataService
        // directamente para evitar la llamada HTTP circular que genera deadlock
        // en PHP-FPM. Pero esa vía necesita credenciales Oracle directas
        // (ORACLE_USERNAME) configuradas — el binario oci8 puede estar
        // compilado sin que este entorno tenga acceso directo a Oracle
        // (probado 2026-08: da ORA-24415/12170 y esta rama lo camuflaba como
        // "cliente no encontrado" sin caer al manager HTTP, que sí funciona
        // aquí). Solo se toma el atajo directo si hay credenciales de verdad.
        if (class_exists(ErpCustomerDataService::class) && extension_loaded('oci8')
            && filled(config('database.connections.oracle.username'))) {
            return $this->fetchDirectFromOracle($email, $phone);
        }

        if (! $this->baseUrl()) {
            return $this->emptyContext('not_configured', 'ERP no configurado');
        }

        if ($this->isCircuitOpen()) {
            return $this->emptyContext('circuit_open', 'ERP temporalmente no disponible');
        }

        try {
            $customer = $this->searchCustomer($email);

            // Fallback: si el email no tiene match en el ERP, buscar por teléfono
            if (! $customer && $phone !== null) {
                $digits = app(PhoneNormalizerService::class)->toDigits($phone);
                if ($digits !== null) {
                    $customer = $this->searchCustomerByPhone($digits);
                }
            }

            // Llegar aquí sin excepción significa que el manager respondió: cerrar
            // el breaker aunque el cliente no exista (una no-coincidencia no es caída).
            $this->recordSuccess();

            if (! $customer) {
                return $this->emptyContext();
            }

            return $this->fetchCustomerData($customer, $email);
        } catch (ConnectionException $e) {
            $this->recordFailure();

            Log::warning('HelpdeskErp: error de conexión al manager API.', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);

            return $this->emptyContext('connection', 'No se pudo conectar al ERP');
        } catch (\Throwable $e) {
            $this->recordFailure();

            Log::warning('HelpdeskErp: error al consultar manager API.', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);

            return $this->emptyContext('unknown', 'Error inesperado al consultar ERP');
        }
    }

    /**
     * Fetch customer context via ErpCustomerDataService (Erp module, mismo proceso).
     * Evita la llamada HTTP circular al manager que genera deadlock en PHP-FPM.
     */
    private function fetchDirectFromOracle(string $email, ?string $phone = null): array
    {
        try {
            $erp = app(ErpCustomerDataService::class);
            $ordersLimit = (int) config('helpdeskErp.orders_limit', 10);
            $invoicesLimit = (int) config('helpdeskErp.invoices_limit', 5);

            $c = $erp->findByEmail($email);

            if (! $c && $phone !== null) {
                $digits = app(PhoneNormalizerService::class)->toDigits($phone);
                if ($digits !== null && strlen($digits) >= 6) {
                    $c = $erp->findByPhone($digits);
                }
            }

            if (! $c) {
                return $this->emptyContext();
            }

            $idcliente = (int) $c['idcliente'];
            $orderRows = $erp->getRecentOrders($idcliente, $ordersLimit);
            $invoiceRows = $erp->getRecentInvoices($idcliente, $invoicesLimit);

            $orders = array_map(fn ($o) => [
                'id' => $o['idpedidocli_central'] ?? null,
                'number' => $o['npedidocli'] ?? null,
                'status' => isset($o['estado']) ? (int) $o['estado'] : null,
                'date' => $o['fpedido'] ?? null,
                'expected_date' => $o['fprevista'] ?? null,
                'served_date' => $o['fservido'] ?? null,
                'warehouse' => $o['idalmacen'] ?? null,
                'type' => $o['tipopedido'] ?? null,
                'observations' => $o['observaciones'] ?? null,
            ], $orderRows);

            $invoices = array_map(fn ($i) => [
                'id' => ($i['serie'] ?? '').''.($i['nfactura'] ?? ''),
                'number' => $i['nfactura'] ?? null,
                'series' => $i['serie'] ?? null,
                'year' => $i['anno'] ?? null,
                'date' => $i['ffactura'] ?? null,
                'status' => isset($i['estado']) ? (int) $i['estado'] : null,
                'simplified' => false,
                'payment_method' => $i['formadepago'] ?? null,
            ], $invoiceRows);

            return [
                'customer' => [
                    'found' => true,
                    'id' => $idcliente,
                    'name' => trim(($c['nombre'] ?? '').' '.($c['apellidos'] ?? '')),
                    'email' => $c['email'] ?? $email,
                    'nif' => $c['cif'] ?? null,
                    'phone' => null,
                    'city' => null,
                    'province' => null,
                    'credit_limit' => null,
                    'balance_pending' => null,
                    'balance_invoiced' => null,
                    'loyalty_points' => null,
                    'payment_terms' => null,
                ],
                'orders' => $orders,
                'invoices' => $invoices,
                'orders_loading' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('HelpdeskErp: error en consulta directa a Erp module.', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);

            return $this->emptyContext('oracle', 'Error al consultar Oracle directamente');
        }
    }

    /**
     * Guarda el link entre el customer Helpdesk y su idcliente Oracle.
     * Idempotente — usa updateOrCreate internamente.
     */
    private function persistErpLink(int $helpdeskCustomerId, int $erpCustomerId): void
    {
        try {
            $customer = Customer::on('helpdesk')->find($helpdeskCustomerId);
            $customer?->linkExternalId('erp', (string) $erpCustomerId, [
                'linked_at' => now()->toIso8601String(),
                'linked_by' => 'auto',
            ]);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskErp: no se pudo guardar link ERP.', [
                'helpdesk_customer_id' => $helpdeskCustomerId,
                'erp_customer_id' => $erpCustomerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function fetchCustomerData(array $customer, string $email): array
    {
        if (empty($customer['id']) || ! is_numeric($customer['id'])) {
            return $this->emptyContext();
        }

        $idcliente = $customer['id'];
        $ordersLimit = config('helpdeskErp.orders_limit', 10);
        $invoicesLimit = config('helpdeskErp.invoices_limit', 5);

        // Paralelizar las 4 llamadas al manager con Http::pool()
        $responses = Http::pool(function (Pool $pool) use ($idcliente, $ordersLimit, $invoicesLimit) {
            return [
                $this->applyClientConfig($pool->as('summary'))
                    ->get($this->url("erp/customer/{$idcliente}")),

                $this->applyClientConfig($pool->as('balance'))
                    ->get($this->url("erp/customer/{$idcliente}/balance")),

                $this->applyClientConfig($pool->as('orders'))
                    ->get($this->url("erp/customer/{$idcliente}/orders"), ['limit' => $ordersLimit]),

                $this->applyClientConfig($pool->as('invoices'))
                    ->get($this->url("erp/customer/{$idcliente}/invoices"), ['limit' => $invoicesLimit]),
            ];
        });

        $summary = $responses['summary']->successful() ? ($responses['summary']->json('data') ?? []) : [];
        $balance = $responses['balance']->successful() ? ($responses['balance']->json('data') ?? []) : [];
        $ordersMeta = $responses['orders']->successful() ? ($responses['orders']->json('meta') ?? []) : [];
        $ordersData = $responses['orders']->successful() ? ($responses['orders']->json('data') ?? []) : [];
        $invoicesData = $responses['invoices']->successful() ? ($responses['invoices']->json('data') ?? []) : [];

        $orders = $this->normalizeOrders($ordersData);
        $invoices = $this->normalizeInvoices($invoicesData);
        $ordersLoading = empty($orders) && ($ordersMeta['loading'] ?? false);

        $result = [
            'customer' => [
                'found' => true,
                'id' => $idcliente,
                'name' => trim(($customer['label'] ?? '').' '.($customer['surnames'] ?? '')),
                'email' => $customer['email'] ?? $email,
                'nif' => $customer['cif'] ?? ($summary['cif'] ?? null),
                'phone' => $summary['phones'][0]['number'] ?? null,
                'city' => $summary['addresses'][0]['city'] ?? null,
                'province' => $summary['addresses'][0]['province'] ?? null,
                'credit_limit' => $balance['risk']['max_allowed'] ?? null,
                'balance_pending' => $balance['balance']['pending'] ?? null,
                'balance_invoiced' => $balance['balance']['invoiced'] ?? null,
                'loyalty_points' => $balance['loyalty_points'] ?? null,
                'payment_terms' => $summary['payment_method_id'] ?? null,
            ],
            'orders' => $orders,
            'invoices' => $invoices,
            'orders_loading' => $ordersLoading,
        ];

        if ($ordersLoading && isset($ordersMeta['retry_after'])) {
            $result['meta'] = ['retry_after' => $ordersMeta['retry_after']];
        }

        return $result;
    }

    /* ── Cache helpers ────────────────────────────────────────────────────── */

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
        // No cachear mientras Oracle aún está cargando los pedidos en background
        if ($result['orders_loading'] ?? false) {
            return 0;
        }

        // Errores transitorios (conexión, configuración, desconocido) — TTL muy corto para no bloquear reintentos
        if (isset($result['_error'])) {
            return 5;
        }

        // Negative cache solo para el caso real de cliente no encontrado (sin error)
        if (! ($result['customer']['found'] ?? false)) {
            return (int) config('helpdeskErp.miss_ttl', 60);
        }

        return (int) config('helpdeskErp.cache_ttl', 600);
    }

    /**
     * Dispara un refresh en background si el caché está cerca de expirar.
     */
    private function maybeScheduleRefresh(string $email, array $cached): void
    {
        $cachedAt = $cached['_cached_at'] ?? 0;
        $ttl = $cached['_ttl'] ?? config('helpdeskErp.cache_ttl', 600);
        $staleGrace = (int) config('helpdeskErp.stale_grace', 60);

        $age = time() - $cachedAt;

        if ($age >= ($ttl - $staleGrace)) {
            RefreshErpContextJob::dispatch($email)->afterCommit();
        }
    }

    /**
     * Elimina las claves internas de metadatos del payload cacheado.
     */
    private function stripMeta(array $cached): array
    {
        unset($cached['_cached_at'], $cached['_ttl']);

        return $cached;
    }

    /* ── Search ───────────────────────────────────────────────────────────── */

    private function searchCustomer(string $email): ?array
    {
        $resp = $this->http()->get($this->url('erp/customer/search'), ['q' => $email]);

        if (! $resp->successful()) {
            return null;
        }

        $results = $resp->json('data') ?? $resp->json() ?? [];

        if (! is_array($results)) {
            return null;
        }

        foreach ($results as $c) {
            if (isset($c['email']) && strtolower($c['email']) === strtolower($email)) {
                return $c;
            }
        }

        // No fallback a results[0]: si no hay coincidencia exacta de email, el cliente no existe.
        // El manager hace búsqueda fuzzy — devolver results[0] atribuiría pedidos de otro cliente.
        return null;
    }

    /**
     * Busca cliente en el ERP por teléfono (dígitos puros, ≥6 caracteres).
     * El manager Oracle detecta que es búsqueda por teléfono cuando q es todo dígitos.
     *
     * Mismo criterio que la búsqueda por email (searchCustomer): nunca tomar
     * results[0] a ciegas. La búsqueda por dígitos del manager es fuzzy
     * (IDCLIENTE / IDTARJETA / CODIGO_INTERNET además de teléfono), así que:
     * con varios candidatos el match es ambiguo y no se atribuye; con uno solo
     * se verifica que su teléfono real coincida (normalizados) antes de usarlo.
     */
    private function searchCustomerByPhone(string $phoneDigits): ?array
    {
        try {
            $resp = $this->http()->get($this->url('erp/customer/search'), ['q' => $phoneDigits]);

            if (! $resp->successful()) {
                return null;
            }

            $results = $resp->json('data') ?? [];

            if (! is_array($results) || $results === []) {
                return null;
            }

            if (count($results) > 1) {
                Log::info('HelpdeskErp: búsqueda por teléfono ambigua — no se atribuye el contexto.', [
                    'candidates' => count($results),
                ]);

                return null;
            }

            $candidate = $results[0];
            $candidateId = isset($candidate['id']) && is_numeric($candidate['id']) ? (int) $candidate['id'] : null;

            if ($candidateId === null || $this->customerHasPhone($candidateId, $phoneDigits) !== true) {
                Log::info('HelpdeskErp: el candidato de la búsqueda por teléfono no tiene ese número — no se atribuye.', [
                    'erp_id' => $candidateId,
                ]);

                return null;
            }

            return $candidate;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Verifica contra la ficha del cliente (erp/customer/{id}) que alguno de
     * sus teléfonos coincide con los dígitos buscados, comparando ambos
     * normalizados (PhoneNormalizerService::similar()).
     *
     * @return bool|null true = coincide, false = no coincide, null = no verificable
     */
    public function customerHasPhone(int $erpCustomerId, string $phoneDigits): ?bool
    {
        try {
            $resp = $this->http()->get($this->url("erp/customer/{$erpCustomerId}"));

            if (! $resp->successful()) {
                return null;
            }

            $phones = $resp->json('data.phones') ?? [];

            if (! is_array($phones)) {
                return null;
            }

            $normalizer = app(PhoneNormalizerService::class);

            foreach ($phones as $phone) {
                $number = is_array($phone) ? ($phone['number'] ?? null) : $phone;

                if ((is_string($number) || is_numeric($number))
                    && $normalizer->similar((string) $number, $phoneDigits)) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('HelpdeskErp: no se pudo verificar el teléfono del candidato ERP.', [
                'erp_id' => $erpCustomerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /* ── Normalizers ──────────────────────────────────────────────────────── */

    private function normalizeOrders(array $data): array
    {
        return array_map(fn ($o) => [
            'id' => $o['id'] ?? null,
            'number' => $o['number'] ?? null,
            'status' => $o['status'] ?? null,
            'date' => $o['date'] ?? null,
            'expected_date' => $o['expected_date'] ?? null,
            'served_date' => $o['served_date'] ?? null,
            'warehouse' => $o['warehouse'] ?? null,
            'type' => $o['type'] ?? null,
            'observations' => $o['observations'] ?? null,
        ], $data);
    }

    private function normalizeInvoices(array $data): array
    {
        return array_map(fn ($i) => [
            'id' => $i['id'] ?? null,
            'number' => $i['number'] ?? null,
            'series' => $i['series'] ?? null,
            'year' => $i['year'] ?? null,
            'date' => $i['date'] ?? null,
            'status' => $i['status'] ?? null,
            'simplified' => $i['simplified'] ?? false,
            'payment_method' => $i['payment_method'] ?? null,
        ], $data);
    }

    /* ── HTTP client ──────────────────────────────────────────────────────── */

    private function http(): PendingRequest
    {
        return $this->applyClientConfig(Http::createPendingRequest());
    }

    private function applyClientConfig(PendingRequest $request): PendingRequest
    {
        $request = $request
            ->timeout(config('helpdeskErp.http_timeout', 15))
            ->acceptJson();

        $requestId = request()?->header('X-Request-Id');
        if ($requestId) {
            $request = $request->withHeaders(['X-Request-Id' => $requestId]);
        }

        $token = $this->token();
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('helpdeskErp.manager_url', ''), '/');
    }

    private function token(): string
    {
        return (string) config('helpdeskErp.bridge_token', '');
    }

    private function url(string $path): string
    {
        return $this->baseUrl().'/api/'.$path;
    }

    private function cacheKey(string $email): string
    {
        return 'erp_ctx_'.md5(strtolower(trim($email)));
    }

    /* ── Pulse metrics ────────────────────────────────────────────────────── */

    private function recordPulse(string $email, float $start, bool $cached, bool $found): void
    {
        if (! app()->bound(Pulse::class)) {
            return;
        }

        \Laravel\Pulse\Facades\Pulse::set('helpdesk_erp_request', PiiMasker::email($email), json_encode([
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'cached' => $cached,
            'found' => $found,
        ], JSON_THROW_ON_ERROR));
    }

    /* ── Circuit breaker ──────────────────────────────────────────────────── */

    /**
     * Clave del contador de fallos consecutivos del manager ERP. Cuando el
     * manager está caído cada request cuelga hasta http_timeout (15s); el
     * breaker corta las llamadas tras N fallos para no arrastrar el inbox
     * durante toda la caída. El estado se comparte entre búsqueda, contexto y
     * detalle de pedido (misma clave).
     */
    private const CIRCUIT_KEY = 'helpdeskerp:circuit_failures';

    private const CIRCUIT_CONFIG_PREFIX = 'helpdeskErp';

    /* ── Empty context ────────────────────────────────────────────────────── */

    private function emptyContext(?string $errorType = null, ?string $errorMessage = null): array
    {
        $base = [
            'customer' => ['found' => false],
            'orders' => [],
            'invoices' => [],
            'orders_loading' => false,
        ];

        if ($errorType) {
            $base['_error'] = ['type' => $errorType, 'message' => $errorMessage];
        }

        return $base;
    }
}
