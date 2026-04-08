<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Circuit breaker pattern for external service calls.
 *
 * States:
 *   CLOSED   – normal operation, requests pass through
 *   OPEN     – too many failures, requests are rejected fast
 *   HALF_OPEN – recovery window: one attempt is allowed to test if the service is back
 */
class CircuitBreaker
{
    private const STATE_CLOSED = 'CLOSED';

    private const STATE_OPEN = 'OPEN';

    private const STATE_HALF_OPEN = 'HALF_OPEN';

    public function __construct(
        private readonly string $key,
        private readonly int $failureThreshold = 5,
        private readonly int $recoverySeconds = 60,
        private readonly string $cachePrefix = 'circuit_breaker',
    ) {}

    /**
     * Returns true when the circuit allows a request through (CLOSED or HALF_OPEN).
     * Returns false when the circuit is OPEN and the request should be rejected.
     */
    public function isAvailable(): bool
    {
        return $this->getState() !== self::STATE_OPEN;
    }

    /**
     * Returns true when the circuit is OPEN and the request should be rejected immediately.
     * HALF_OPEN (recovery window reached) returns false to allow one test request through.
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Record a successful call: reset failure count and close the circuit.
     */
    public function recordSuccess(): void
    {
        try {
            Cache::forget($this->getCacheKey('failures'));
            Cache::forget($this->getCacheKey('opened_at'));
        } catch (\Throwable) {
            // Cache unavailable — silently ignore to avoid masking the real response
        }
    }

    /**
     * Record a failed call: increment failure counter and open the circuit
     * once the threshold is reached.
     */
    public function recordFailure(): void
    {
        try {
            $failures = Cache::increment($this->getCacheKey('failures'));

            if ($failures >= $this->failureThreshold) {
                // (Re)set the opened_at timestamp so the recovery window starts now
                Cache::put($this->getCacheKey('opened_at'), now()->timestamp);
            }
        } catch (\Throwable) {
            // Cache unavailable — silently ignore
        }
    }

    /**
     * Returns the current circuit state: CLOSED, OPEN, or HALF_OPEN.
     */
    public function getState(): string
    {
        try {
            $failures = (int) Cache::get($this->getCacheKey('failures'), 0);

            if ($failures < $this->failureThreshold) {
                return self::STATE_CLOSED;
            }

            $openedAt = Cache::get($this->getCacheKey('opened_at'));

            if ($openedAt && (now()->timestamp - (int) $openedAt) >= $this->recoverySeconds) {
                return self::STATE_HALF_OPEN;
            }

            return self::STATE_OPEN;
        } catch (\Throwable) {
            // If cache is unavailable, allow requests through (fail open)
            return self::STATE_CLOSED;
        }
    }

    private function getCacheKey(string $suffix): string
    {
        return "{$this->cachePrefix}:{$this->key}:{$suffix}";
    }
}
