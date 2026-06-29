<?php

namespace Modules\Campaign\Services;

use Throwable;

/**
 * Decide cuánto tiempo esperar antes de reintentar un envío fallido,
 * basado en el tipo de error detectado.
 */
class SendRetryPolicy
{
    /**
     * Mapeo de patrones de error → segundos de espera.
     * null significa "no reintentar" (hard failure).
     */
    protected array $rules = [
        // Rate limit / Throttling
        'rate limit' => 300,
        'too many requests' => 300,
        'throttled' => 300,
        'temporarily unavailable' => 300,

        // DNS / Network
        'dns' => 60,
        'getaddrinfo' => 60,
        'connection timed out' => 120,
        'network' => 120,

        // Greylisting / Soft bounce
        'greylist' => 600,
        'try again' => 600,
        'deferred' => 600,
        '4.7.1' => 600,
        '4.2.1' => 600,
        '4.3.2' => 600,
        '4.4.1' => 600,
        '4.4.2' => 600,
        '4.4.5' => 600,
        '4.7.0' => 600,

        // Out of credits / quota
        'out of credits' => null,
        'quota exceeded' => 3600,
        'daily limit' => 3600,

        // Auth / Config (no reintentar)
        'authentication failed' => null,
        'invalid api key' => null,
        'unauthorized' => null,
        'bad request' => null,

        // Default
        '__default__' => 300,
    ];

    public function getRetryDelay(Throwable $exception): ?int
    {
        $message = strtolower($exception->getMessage());

        foreach ($this->rules as $pattern => $seconds) {
            if ($pattern === '__default__') {
                continue;
            }
            if (str_contains($message, $pattern)) {
                return $seconds;
            }
        }

        return $this->rules['__default__'];
    }

    public function shouldRetry(Throwable $exception): bool
    {
        return $this->getRetryDelay($exception) !== null;
    }

    public function getDelay(Throwable $exception): int
    {
        return $this->getRetryDelay($exception) ?? 0;
    }
}
