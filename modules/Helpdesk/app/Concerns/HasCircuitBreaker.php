<?php

namespace Modules\Helpdesk\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Shared circuit breaker for outbound integrations (ERP, PrestaShop, ...):
 * after N consecutive failures within a time window, short-circuits further
 * calls instead of letting every request hang until the upstream's own
 * timeout. Extracted from ErpContextService and PrestashopContextService,
 * which duplicated this exact logic.
 *
 * Consuming services must define two constants:
 *   - `CIRCUIT_KEY` — cache key namespacing this breaker's failure counter
 *   - `CIRCUIT_CONFIG_PREFIX` — config() prefix for the tunables:
 *     "{prefix}.circuit_failure_threshold" and "{prefix}.circuit_open_seconds"
 */
trait HasCircuitBreaker
{
    private function isCircuitOpen(): bool
    {
        $threshold = (int) config(static::CIRCUIT_CONFIG_PREFIX.'.circuit_failure_threshold', 5);

        return (int) Cache::get(static::CIRCUIT_KEY, 0) >= $threshold;
    }

    private function recordFailure(): void
    {
        $openSeconds = (int) config(static::CIRCUIT_CONFIG_PREFIX.'.circuit_open_seconds', 30);

        // Cache::add fija el TTL de la ventana solo en el primer fallo; los
        // increments posteriores lo mantienen (Redis conserva el TTL en INCR).
        Cache::add(static::CIRCUIT_KEY, 0, $openSeconds);
        Cache::increment(static::CIRCUIT_KEY);
    }

    private function recordSuccess(): void
    {
        Cache::forget(static::CIRCUIT_KEY);
    }
}
