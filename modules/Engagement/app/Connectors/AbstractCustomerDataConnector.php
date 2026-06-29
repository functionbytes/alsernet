<?php

namespace Modules\Engagement\Connectors;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Contracts\CustomerDataConnector;
use Modules\Engagement\Models\PlatformIntegration;

/**
 * Implementación base que devuelve "no soportado" para todas las acciones.
 * Connectors concretos sobrescriben sólo los métodos que la plataforma
 * realmente expone.
 *
 * Provee helpers compartidos: cache key, respuesta normalizada, logging.
 */
abstract class AbstractCustomerDataConnector implements CustomerDataConnector
{
    /**
     * TTL por acción en segundos. Override en hijos para personalizar.
     */
    protected array $cacheTtl = [
        'profile' => 300,
        'orders' => 300,
        'orderDetail' => 300,
        'addresses' => 1800,
        'returns' => 300,
        'vouchers' => 300,
        'cart' => 60,
        'messages' => 300,
        'deliveryNotes' => 300,
        'invoices' => 300,
        'payments' => 60,
        'debts' => 30,
        'balance' => 30,
        'bonuses' => 300,
        'loyaltyPoints' => 60,
    ];

    /**
     * Lista de acciones soportadas por esta plataforma.
     */
    protected array $supportedActions = [];

    public function supports(string $action): bool
    {
        return in_array($action, $this->supportedActions, true);
    }

    public function profile(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('profile');
    }

    public function orders(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('orders');
    }

    public function orderDetail(PlatformIntegration $i, string|int $orderId, bool $force = false): array
    {
        return $this->unsupported('orderDetail');
    }

    public function addresses(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('addresses');
    }

    public function returns(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('returns');
    }

    public function vouchers(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('vouchers');
    }

    public function cart(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('cart');
    }

    public function messages(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('messages');
    }

    public function deliveryNotes(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('deliveryNotes');
    }

    public function invoices(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('invoices');
    }

    public function payments(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('payments');
    }

    public function debts(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('debts');
    }

    public function balance(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('balance');
    }

    public function bonuses(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('bonuses');
    }

    public function loyaltyPoints(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->unsupported('loyaltyPoints');
    }

    public function invalidateCache(PlatformIntegration $integration, array $lookup): void
    {
        foreach ($this->supportedActions as $action) {
            Cache::forget($this->cacheKey($integration, $action, $lookup));
        }
    }

    /**
     * Llave de cache estable: platform + integration + action + hash(lookup ordenado).
     */
    protected function cacheKey(PlatformIntegration $integration, string $action, array $lookup): string
    {
        $normalized = array_filter($lookup, fn ($v) => $v !== null && $v !== '');
        ksort($normalized);

        return sprintf(
            'engagement.connector.%s.%d.%s.%s',
            $this->platform(),
            $integration->id,
            $action,
            md5(json_encode($normalized)),
        );
    }

    /**
     * Wrapper de cache + ejecución. Connectors concretos pasan un closure
     * con la llamada HTTP real.
     */
    protected function remember(
        PlatformIntegration $integration,
        string $action,
        array $lookup,
        bool $force,
        \Closure $resolver,
    ): array {
        $key = $this->cacheKey($integration, $action, $lookup);

        if (! $force && ($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $data = $resolver();

            $response = $this->ok($data);

            $ttl = $this->cacheTtl[$action] ?? 300;
            Cache::put($key, $response, $ttl);

            return $response;
        } catch (\Throwable $e) {
            Log::warning(sprintf('[connector:%s] %s failed', $this->platform(), $action), [
                'integration_id' => $integration->id,
                'lookup' => array_keys($lookup),
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage());
        }
    }

    /**
     * Respuesta exitosa normalizada.
     */
    protected function ok(?array $data): array
    {
        return [
            'ok' => true,
            'data' => $data,
            'error' => null,
            'cached_at' => now()->toIso8601String(),
            'platform' => $this->platform(),
        ];
    }

    /**
     * Respuesta de error normalizada.
     */
    protected function error(string $message): array
    {
        return [
            'ok' => false,
            'data' => null,
            'error' => $message,
            'cached_at' => null,
            'platform' => $this->platform(),
        ];
    }

    /**
     * Respuesta "acción no soportada" (no es error, simplemente la plataforma
     * no expone esa categoría — ej: PrestaShop no tiene "deudas").
     */
    protected function unsupported(string $action): array
    {
        return [
            'ok' => false,
            'data' => null,
            'error' => 'unsupported',
            'cached_at' => null,
            'platform' => $this->platform(),
        ];
    }
}
