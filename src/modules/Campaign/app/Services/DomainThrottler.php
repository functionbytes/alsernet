<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Cache;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;

/**
 * Rate limit por dominio destino (gmail.com, outlook.com, etc.).
 * Evita saturar un solo proveedor de email y mejora reputación de IP.
 */
class DomainThrottler
{
    /**
     * Límites por dominio: [dominio => [ventana_minutos, max_envios]]
     */
    protected array $defaultLimits = [
        'gmail.com' => [1, 120],
        'googlemail.com' => [1, 120],
        'outlook.com' => [1, 100],
        'hotmail.com' => [1, 100],
        'live.com' => [1, 100],
        'yahoo.com' => [1, 80],
        'icloud.com' => [1, 60],
        'aol.com' => [1, 60],
    ];

    public function throttle(string $email, ?\Closure $callback = null): void
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1) ?: '');
        if (empty($domain)) {
            return;
        }

        $limits = array_merge(
            $this->defaultLimits,
            config('campaign.domain_throttle.limits', [])
        );

        if (! isset($limits[$domain])) {
            return;
        }

        [$windowMinutes, $max] = $limits[$domain];
        $key = "domain-throttle:{$domain}";
        $current = Cache::increment($key);

        if ($current === 1) {
            Cache::put($key, 1, now()->addMinutes($windowMinutes));
        }

        if ($current > $max) {
            $wait = Cache::get("{$key}:expires") ?? ($windowMinutes * 60);
            throw new RateLimitExceeded(
                "Domain throttle exceeded for {$domain}",
                (int) $wait
            );
        }
    }

    public function remaining(string $email): ?int
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1) ?: '');
        $limits = array_merge($this->defaultLimits, config('campaign.domain_throttle.limits', []));

        if (! isset($limits[$domain])) {
            return null;
        }

        [, $max] = $limits[$domain];
        $current = (int) Cache::get("domain-throttle:{$domain}", 0);

        return max(0, $max - $current);
    }
}
