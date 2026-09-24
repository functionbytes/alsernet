<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Concerns\HasCircuitBreaker;

/**
 * Lecturas de Gestión (ERP) para el chat: una llamada por sección del
 * manager, normalizada a {state, data, message, reason, fetched_at} y
 * cacheada por cliente y sección.
 *
 * - El ERP es SOLO LECTURA desde aquí: no hay ningún método de escritura.
 * - Comparte el cortacircuitos de ErpContextService (misma clave de caché y
 *   misma configuración), así que una caída del manager detectada por
 *   cualquiera de los dos corta el tráfico de ambos.
 * - "Lost connection and no reconnector available" es un fallo del manager
 *   tras un error Oracle previo: se reintenta UNA vez.
 * - Una respuesta "loading" (escaneo de pedidos en curso) nunca se cachea.
 */
class ErpChatService
{
    use HasCircuitBreaker;

    /** Misma clave que ErpContextService: un único cortacircuitos para el ERP. */
    private const CIRCUIT_KEY = 'helpdeskerp:circuit_failures';

    private const CIRCUIT_CONFIG_PREFIX = 'helpdeskErp';

    private const CACHE_PREFIX = 'helpdeskerp:chat:';

    /** Secciones nuevas del manager: un fallo suyo es "no disponible", no error. */
    private const SOFT_SECTIONS = ['returns'];

    public function __construct(
        private readonly ErpChatResponseNormalizer $normalizer,
    ) {}

    /* ── Secciones ────────────────────────────────────────────────────── */

    /**
     * Una sección de la lista blanca (ErpChatSections::ALL).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function section(int $erpId, string $section, array $params = [], bool $fresh = false): array
    {
        if (! ErpChatSections::exists($section)) {
            return $this->normalizer->make('unavailable', null, ErpChatResponseNormalizer::MSG_UNAVAILABLE, 'unknown_section');
        }

        return $this->run([$section => $this->sectionSpec($erpId, $section, $params)], $fresh)[$section];
    }

    /**
     * Varias secciones en paralelo (Http::pool) con una sola ida al manager.
     *
     * @param  array<string, array{0: string, 1?: array<string, mixed>}>  $requests  clave => [sección, params]
     * @return array<string, array<string, mixed>>
     */
    public function many(int $erpId, array $requests, bool $fresh = false): array
    {
        $specs = [];
        $out = [];

        foreach ($requests as $key => $request) {
            $section = $request[0];

            if (! ErpChatSections::exists($section)) {
                $out[$key] = $this->normalizer->make('unavailable', null, ErpChatResponseNormalizer::MSG_UNAVAILABLE, 'unknown_section');

                continue;
            }

            $specs[$key] = $this->sectionSpec($erpId, $section, $request[1] ?? []);
        }

        return $out + $this->run($specs, $fresh);
    }

    public function summary(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'summary', [], $fresh);
    }

    public function personal(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'personal', [], $fresh);
    }

    public function contact(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'contact', [], $fresh);
    }

    public function addresses(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'addresses', [], $fresh);
    }

    public function catalogs(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'catalogs', [], $fresh);
    }

    public function quotas(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'quotas', [], $fresh);
    }

    public function cards(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'cards', [], $fresh);
    }

    public function accounts(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'accounts', [], $fresh);
    }

    /**
     * @param  array{limit?: int, offset?: int, status?: string, from?: string, to?: string}  $filters
     */
    public function orders(int $erpId, array $filters = [], bool $fresh = false): array
    {
        return $this->section($erpId, 'orders', $filters, $fresh);
    }

    public function deliveryNotes(int $erpId, array $filters = [], bool $fresh = false): array
    {
        return $this->section($erpId, 'delivery-notes', $filters, $fresh);
    }

    public function invoices(int $erpId, array $filters = [], bool $fresh = false): array
    {
        return $this->section($erpId, 'invoices', $filters, $fresh);
    }

    public function payments(int $erpId, array $filters = [], bool $fresh = false): array
    {
        return $this->section($erpId, 'payments', $filters, $fresh);
    }

    public function debts(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'debts', [], $fresh);
    }

    public function balance(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'balance', [], $fresh);
    }

    public function vouchers(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'vouchers', [], $fresh);
    }

    public function bonuses(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'bonuses', [], $fresh);
    }

    public function loyaltyPoints(int $erpId, bool $fresh = false): array
    {
        return $this->section($erpId, 'loyalty-points', [], $fresh);
    }

    public function returns(int $erpId, array $filters = [], bool $fresh = false): array
    {
        return $this->section($erpId, 'returns', $filters, $fresh);
    }

    /* ── Detalles ─────────────────────────────────────────────────────── */

    public function orderDetail(int $erpId, int $orderId, bool $fresh = false): array
    {
        return $this->run(['detail' => $this->orderSpecs($erpId, $orderId)['detail']], $fresh)['detail'];
    }

    public function orderHistory(int $erpId, int $orderId, bool $fresh = false): array
    {
        return $this->run(['history' => $this->orderSpecs($erpId, $orderId)['history']], $fresh)['history'];
    }

    public function orderShipping(int $erpId, int $orderId, bool $fresh = false): array
    {
        return $this->run(['shipping' => $this->orderSpecs($erpId, $orderId)['shipping']], $fresh)['shipping'];
    }

    /**
     * Detalle + historial + envío del pedido en paralelo.
     *
     * @return array{detail: array<string, mixed>, history: array<string, mixed>, shipping: array<string, mixed>}
     */
    public function orderBundle(int $erpId, int $orderId, bool $fresh = false): array
    {
        /** @var array{detail: array<string, mixed>, history: array<string, mixed>, shipping: array<string, mixed>} */
        return $this->run($this->orderSpecs($erpId, $orderId), $fresh);
    }

    public function deliveryNoteDetail(int $erpId, int $deliveryId, bool $fresh = false): array
    {
        return $this->run(['detail' => [
            'cache' => "delivery-note:{$deliveryId}",
            'erp' => $erpId,
            'path' => "delivery-notes/{$deliveryId}",
            'query' => [],
            'ttl' => 'detail',
            'expect' => $deliveryId,
        ]], $fresh)['detail'];
    }

    public function invoiceDetail(int $erpId, int $invoiceId, bool $fresh = false): array
    {
        return $this->run(['detail' => [
            'cache' => "invoice:{$invoiceId}",
            'erp' => $erpId,
            'path' => "invoices/{$invoiceId}",
            'query' => [],
            'ttl' => 'detail',
            'expect' => $invoiceId,
        ]], $fresh)['detail'];
    }

    /**
     * Invalida todo lo cacheado de un cliente ERP (sube la versión de sus
     * claves: no hace falta conocerlas para "borrarlas").
     */
    public function forgetCustomer(int $erpId): void
    {
        $key = self::CACHE_PREFIX.'ver:'.$erpId;
        Cache::forever($key, (int) Cache::get($key, 0) + 1);
    }

    /* ── Motor ────────────────────────────────────────────────────────── */

    /**
     * @param  array<string, array{cache: string, erp: int, path: string, query: array<string, scalar>, ttl: string, expect: int|string|null}>  $specs
     * @return array<string, array<string, mixed>>
     */
    private function run(array $specs, bool $fresh): array
    {
        $results = [];
        $misses = [];

        foreach ($specs as $key => $spec) {
            $cached = $fresh ? null : Cache::get($this->cacheKey($spec));

            if (is_array($cached)) {
                $cached['cached'] = true;
                $results[$key] = $cached;
            } else {
                $misses[$key] = $spec;
            }
        }

        if ($misses === []) {
            return $this->ordered($specs, $results);
        }

        if ($this->baseUrl() === '') {
            foreach ($misses as $key => $spec) {
                $results[$key] = $this->normalizer->make('down', null, 'Gestión no está configurada.', 'not_configured');
            }

            return $this->ordered($specs, $results);
        }

        if ($this->isCircuitOpen()) {
            foreach ($misses as $key => $spec) {
                $results[$key] = $this->normalizer->make('down', null, ErpChatResponseNormalizer::MSG_DOWN, 'circuit_open');
            }

            return $this->ordered($specs, $results);
        }

        $fetched = $this->fetch($misses);

        // Reintento único ante "Lost connection and no reconnector available".
        $lost = array_filter($fetched, fn (array $r): bool => ($r['reason'] ?? null) === 'lost_connection');
        if ($lost !== []) {
            $fetched = array_replace($fetched, $this->fetch(array_intersect_key($misses, $lost)));
        }

        $this->trackCircuit($fetched);

        foreach ($fetched as $key => $result) {
            $this->store($misses[$key], $result);
            $result['cached'] = false;
            $results[$key] = $result;
        }

        return $this->ordered($specs, $results);
    }

    /**
     * @param  array<string, array<string, mixed>>  $specs
     * @return array<string, array<string, mixed>>
     */
    private function fetch(array $specs): array
    {
        $responses = [];

        if (count($specs) === 1) {
            $key = array_key_first($specs);
            $spec = $specs[$key];

            try {
                $responses[$key] = $this->configure(Http::createPendingRequest())
                    ->get($this->url($spec), $spec['query']);
            } catch (\Throwable $e) {
                $responses[$key] = $e;
            }
        } else {
            try {
                $responses = Http::pool(function (Pool $pool) use ($specs): array {
                    $out = [];
                    foreach ($specs as $key => $spec) {
                        $out[] = $this->configure($pool->as((string) $key))->get($this->url($spec), $spec['query']);
                    }

                    return $out;
                });
            } catch (\Throwable $e) {
                $responses = array_fill_keys(array_keys($specs), $e);
            }
        }

        $out = [];
        foreach ($specs as $key => $spec) {
            // Http::pool devuelve la ConnectionException como VALOR, no la lanza.
            $response = $responses[$key] ?? null;
            $out[$key] = $this->normalizer->normalize($response, $spec['expect'] ?? null, (bool) ($spec['soft'] ?? false));

            // Tarjetas/IBAN enmascarados antes de cachear: nunca se guarda el PAN.
            if (isset($spec['section']) && $out[$key]['state'] === 'ok') {
                $out[$key]['data'] = ErpChatSections::maskSensitive((string) $spec['section'], $out[$key]['data']);
            }

            if ($out[$key]['state'] === 'down') {
                Log::info('HelpdeskErp chat: sección sin respuesta válida del manager.', [
                    'path' => $spec['path'],
                    'erp_id' => $spec['erp'],
                    'reason' => $out[$key]['reason'],
                    'error' => $response instanceof \Throwable ? $response->getMessage() : null,
                ]);
            }
        }

        return $out;
    }

    /**
     * Cortacircuitos: cuenta UN fallo si ninguna petición llegó al manager
     * (conexión/timeout/5xx) y lo cierra en cuanto alguna responde. Un fallo
     * Oracle con respuesta del manager (GRANT, Lost connection) no es caída.
     *
     * @param  array<string, array<string, mixed>>  $results
     */
    private function trackCircuit(array $results): void
    {
        $failed = fn (array $r): bool => in_array($r['reason'] ?? null, ['connection', 'server_error'], true);

        if ($results !== [] && count(array_filter($results, $failed)) === count($results)) {
            $this->recordFailure();

            return;
        }

        $this->recordSuccess();
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array<string, mixed>  $result
     */
    private function store(array $spec, array $result): void
    {
        $ttls = (array) config('helpdeskErp.chat_ttl', []);

        $ttl = match ($result['state']) {
            'ok' => (int) ($spec['ttl'] === 'detail' ? ($ttls['detail'] ?? 1800) : ($ttls['ok'] ?? 300)),
            'blocked' => (int) ($ttls['blocked'] ?? 600),
            'unavailable' => (int) ($ttls['unavailable'] ?? 300),
            'down' => (int) ($ttls['down'] ?? 30),
            default => 0, // loading: nunca
        };

        // Un "no pertenece a este cliente" o un corte del cortacircuitos no
        // se guarda como si fuera la respuesta del manager.
        if (in_array($result['reason'] ?? null, ['circuit_open', 'not_configured'], true)) {
            $ttl = 0;
        }

        if ($ttl > 0) {
            Cache::put($this->cacheKey($spec), $result, $ttl);
        }
    }

    /**
     * @param  array<string, mixed>  $specs
     * @param  array<string, array<string, mixed>>  $results
     * @return array<string, array<string, mixed>>
     */
    private function ordered(array $specs, array $results): array
    {
        $out = [];
        foreach (array_keys($specs) as $key) {
            $out[$key] = $results[$key];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sectionSpec(int $erpId, string $section, array $params): array
    {
        $query = ErpChatSections::paramsFor($section, $params);

        return [
            'cache' => $section.':'.md5((string) json_encode($query)),
            'erp' => $erpId,
            'path' => ErpChatSections::ALL[$section]['path'],
            'query' => $query,
            'ttl' => 'ok',
            'expect' => null,
            'soft' => in_array($section, self::SOFT_SECTIONS, true),
            'section' => $section,
        ];
    }

    /**
     * @return array{detail: array<string, mixed>, history: array<string, mixed>, shipping: array<string, mixed>}
     */
    private function orderSpecs(int $erpId, int $orderId): array
    {
        return [
            'detail' => ['cache' => "order:{$orderId}", 'erp' => $erpId, 'path' => "orders/{$orderId}", 'query' => [], 'ttl' => 'detail', 'expect' => $orderId],
            'history' => ['cache' => "order-history:{$orderId}", 'erp' => $erpId, 'path' => "orders/{$orderId}/history", 'query' => [], 'ttl' => 'ok', 'expect' => null, 'soft' => true],
            'shipping' => ['cache' => "order-shipping:{$orderId}", 'erp' => $erpId, 'path' => "orders/{$orderId}/shipping", 'query' => [], 'ttl' => 'ok', 'expect' => null, 'soft' => true],
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function cacheKey(array $spec): string
    {
        $version = (int) Cache::get(self::CACHE_PREFIX.'ver:'.$spec['erp'], 0);

        return self::CACHE_PREFIX.$spec['erp'].':v'.$version.':'.$spec['cache'];
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function url(array $spec): string
    {
        $path = trim((string) $spec['path'], '/');

        return $this->baseUrl().'/api/erp/customer/'.$spec['erp'].($path !== '' ? '/'.$path : '');
    }

    private function configure(PendingRequest $request): PendingRequest
    {
        $request = $request
            ->timeout((int) config('helpdeskErp.http_timeout', 15))
            ->connectTimeout(5)
            ->acceptJson();

        $requestId = request()?->header('X-Request-Id');
        if ($requestId) {
            $request = $request->withHeaders(['X-Request-Id' => $requestId]);
        }

        $token = (string) config('helpdeskErp.bridge_token', '');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('helpdeskErp.manager_url', ''), '/');
    }
}
