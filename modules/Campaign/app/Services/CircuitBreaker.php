<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Circuit breaker para sending servers.
 * Si un servidor falla N veces en M minutos, se abre el circuito
 * y se ignora durante un tiempo de cooldown.
 */
class CircuitBreaker
{
    public function __construct(
        protected int $failureThreshold = 5,
        protected int $windowSeconds = 300,
        protected int $cooldownSeconds = 600,
    ) {}

    public function recordFailure(int $serverId): void
    {
        $key = "cb:failure:{$serverId}";
        $count = Cache::increment($key);
        if ($count === 1) {
            Cache::put($key, 1, now()->addSeconds($this->windowSeconds));
        }
    }

    public function recordSuccess(int $serverId): void
    {
        Cache::forget("cb:failure:{$serverId}");
        Cache::forget("cb:open:{$serverId}");
    }

    public function isOpen(int $serverId): bool
    {
        // Si está en cooldown, el circuito sigue abierto
        if (Cache::has("cb:open:{$serverId}")) {
            return true;
        }

        $failures = (int) Cache::get("cb:failure:{$serverId}", 0);
        if ($failures >= $this->failureThreshold) {
            Cache::put("cb:open:{$serverId}", true, now()->addSeconds($this->cooldownSeconds));

            return true;
        }

        return false;
    }

    public function status(int $serverId): string
    {
        return $this->isOpen($serverId) ? 'open' : 'closed';
    }

    public function reset(int $serverId): void
    {
        Cache::forget("cb:failure:{$serverId}");
        Cache::forget("cb:open:{$serverId}");
    }
}
