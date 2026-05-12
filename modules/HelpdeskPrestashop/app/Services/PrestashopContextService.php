<?php

namespace Modules\HelpdeskPrestashop\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskPrestashop\Jobs\RefreshPsContextJob;

class PrestashopContextService
{
    private const EMPTY_CONTEXT = [
        'customer' => ['found' => false],
        'orders' => [],
        'carts' => [],
    ];

    public function getCustomerContext(string $email): array
    {
        $key = 'prestashop_ctx_'.md5(strtolower(trim($email)));
        $cached = Cache::get($key);

        if ($cached !== null) {
            $this->maybeRevalidate($email, $cached);

            unset($cached['_cached_at'], $cached['_ttl']);

            return $cached;
        }

        $result = $this->callApi('customer.helpdesk_context', ['lookup' => ['email' => $email]]) ?? self::EMPTY_CONTEXT;

        $this->putInCache($key, $result);

        return $result;
    }

    public function forgetCache(string $email): void
    {
        Cache::forget('prestashop_ctx_'.md5(strtolower(trim($email))));
    }

    public function getOrderDetail(int $orderId, ?string $customerEmail = null): ?array
    {
        $payload = ['order_id' => $orderId];

        if ($customerEmail !== null) {
            $payload['lookup'] = ['email' => $customerEmail];
        }

        return $this->callApi('order.detail', $payload);
    }

    public function startOrderReturn(int $orderId, array $items, ?string $customerEmail = null, ?string $idempotencyKey = null): ?array
    {
        $payload = ['order_id' => $orderId, 'items' => $items];

        if ($customerEmail !== null) {
            $payload['lookup'] = ['email' => $customerEmail];
        }

        return $this->callApi('order.start_return', $payload, $idempotencyKey);
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

    /**
     * Dispara revalidación en background si el entry está cerca de expirar.
     */
    private function maybeRevalidate(string $email, array $cached): void
    {
        $age = time() - ($cached['_cached_at'] ?? 0);
        $effectiveTtl = $cached['_ttl'] ?? config('helpdeskprestashop.cache_ttl', 300);
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

    /**
     * Central HTTP caller: builds the HMAC signature (with timestamp) and dispatches the request.
     * Write actions accept an optional idempotency key; one is auto-generated when not provided.
     *
     * Returns the decoded JSON body on success, or null on any failure.
     */
    private function callApi(string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        $apiUrl = config('helpdeskprestashop.api_url', '');
        $secret = config('helpdeskprestashop.webhook_secret', '');

        if (! $apiUrl || ! $secret) {
            Log::info('HelpdeskPrestashop: API URL o secreto no configurados — se devuelve null.', [
                'action' => $action,
            ]);

            return null;
        }

        $bodyJson = json_encode(array_merge(['action' => $action], $payload));
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.':'.$bodyJson, $secret);

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
            $response = Http::timeout(config('helpdeskprestashop.http_timeout', 10))
                ->withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($apiUrl);

            if (! $response->successful()) {
                Log::warning('HelpdeskPrestashop: respuesta no exitosa.', [
                    'action' => $action,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $decoded = $response->json();

            if (! ($decoded['ok'] ?? false)) {
                Log::warning('HelpdeskPrestashop: API devolvió ok=false.', [
                    'action' => $action,
                    'error' => $decoded['error'] ?? 'unknown',
                ]);

                return null;
            }

            return $decoded['data'] ?? null;

        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop: error al llamar al API.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
