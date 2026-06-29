<?php

namespace Modules\Erp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 *
 * Prevents cascading failures when external services (like Oracle) are down.
 * Three states: CLOSED (normal), OPEN (failing), HALF_OPEN (recovering)
 *
 * Flow:
 * 1. CLOSED: Normal operation, requests pass through
 * 2. If failures exceed threshold → OPEN
 * 3. OPEN: Reject all requests immediately (fail fast)
 * 4. After recovery timeout → HALF_OPEN (try one request)
 * 5. If success → CLOSED, if failure → OPEN again
 */
class CircuitBreaker
{
    /**
     * Number of consecutive failures before opening the circuit
     */
    private const FAILURE_THRESHOLD = 5;

    /**
     * Seconds to wait before attempting recovery (half-open state)
     */
    private const RECOVERY_TIMEOUT = 300; // 5 minutes

    /**
     * Seconds to cache circuit state
     */
    private const STATE_TTL = 3600; // 1 hour

    /**
     * Check if the circuit breaker is open (blocking requests)
     *
     * @param  string  $service  Service identifier (e.g., 'oracle', 'redis')
     * @return bool True if circuit is open (service should not be called)
     */
    public function isOpen(string $service): bool
    {
        $failures = Cache::get($this->getFailureKey($service), 0);
        $openedAt = Cache::get($this->getOpenedAtKey($service));

        // Circuit is closed (normal operation)
        if ($failures < self::FAILURE_THRESHOLD) {
            return false;
        }

        // Circuit is open but recovery timeout has passed → try half-open
        if ($openedAt && $openedAt->copy()->addSeconds(self::RECOVERY_TIMEOUT)->isPast()) {
            Log::info("🟡 Circuit breaker entering HALF_OPEN state for {$service}", [
                'opened_at' => $openedAt,
                'recovery_timeout' => self::RECOVERY_TIMEOUT,
                'failures' => $failures,
            ]);

            // Reset to half-open state (allow one test request)
            Cache::put($this->getFailureKey($service), self::FAILURE_THRESHOLD - 1, self::STATE_TTL);

            return false; // Allow request through
        }

        // Circuit is fully open - block all requests
        return true;
    }

    /**
     * Record a failed attempt to the service
     *
     * @param  string  $service  Service identifier
     */
    public function recordFailure(string $service): void
    {
        $failures = Cache::increment($this->getFailureKey($service), 1, self::STATE_TTL);

        Log::warning("⚠️ Circuit breaker failure recorded for {$service}", [
            'failures' => $failures,
            'threshold' => self::FAILURE_THRESHOLD,
        ]);

        // If threshold reached, open the circuit
        if ($failures >= self::FAILURE_THRESHOLD) {
            Cache::put($this->getOpenedAtKey($service), now(), self::STATE_TTL);

            Log::error("🔴 Circuit breaker OPENED for {$service} - service is failing", [
                'failures' => $failures,
                'recovery_timeout_seconds' => self::RECOVERY_TIMEOUT,
                'next_attempt_at' => now()->addSeconds(self::RECOVERY_TIMEOUT)->toIso8601String(),
            ]);
        }
    }

    /**
     * Record a successful attempt to the service
     *
     * @param  string  $service  Service identifier
     */
    public function recordSuccess(string $service): void
    {
        $wasOpen = $this->isOpen($service);

        // Reset circuit breaker state
        Cache::forget($this->getFailureKey($service));
        Cache::forget($this->getOpenedAtKey($service));

        if ($wasOpen) {
            Log::info("✅ Circuit breaker CLOSED for {$service} - service recovered", [
                'service' => $service,
            ]);
        }
    }

    /**
     * Get current state of the circuit breaker
     *
     * @param  string  $service  Service identifier
     * @return array State information
     */
    public function getState(string $service): array
    {
        $failures = Cache::get($this->getFailureKey($service), 0);
        $openedAt = Cache::get($this->getOpenedAtKey($service));

        $state = 'CLOSED'; // Normal operation
        if ($failures >= self::FAILURE_THRESHOLD) {
            if ($openedAt && $openedAt->copy()->addSeconds(self::RECOVERY_TIMEOUT)->isPast()) {
                $state = 'HALF_OPEN'; // Trying to recover
            } else {
                $state = 'OPEN'; // Blocking requests
            }
        }

        return [
            'service' => $service,
            'state' => $state,
            'failures' => $failures,
            'threshold' => self::FAILURE_THRESHOLD,
            'opened_at' => $openedAt?->toIso8601String(),
            'recovery_timeout' => self::RECOVERY_TIMEOUT,
            'next_attempt_at' => $openedAt?->addSeconds(self::RECOVERY_TIMEOUT)->toIso8601String(),
        ];
    }

    /**
     * Manually reset the circuit breaker (helpdesks intervention)
     *
     * @param  string  $service  Service identifier
     */
    public function reset(string $service): void
    {
        Cache::forget($this->getFailureKey($service));
        Cache::forget($this->getOpenedAtKey($service));

        Log::info("🔧 Circuit breaker manually reset for {$service}", [
            'service' => $service,
        ]);
    }

    /**
     * Get cache key for failure count
     */
    private function getFailureKey(string $service): string
    {
        return "circuit_breaker_{$service}_failures";
    }

    /**
     * Get cache key for opened timestamp
     */
    private function getOpenedAtKey(string $service): string
    {
        return "circuit_breaker_{$service}_opened_at";
    }
}
