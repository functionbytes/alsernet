<?php

namespace Modules\Campaign\Library;

use Illuminate\Support\Facades\Cache;
use Modules\Campaign\Models\Campaign;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Modules\CampaignSendingServers\Library\RateLimit;

/**
 * Rate tracker por campaña. Limita la velocidad de envío de una campaña
 * específica independientemente del servidor usado.
 */
class CampaignRateTracker
{
    private string $key;

    private array $limits;

    public function __construct(string $campaignUid, array $limits = [])
    {
        $this->key = "campaign-rate-{$campaignUid}";
        $this->limits = $limits;
    }

    /**
     * Crea un tracker con los límites por defecto del proyecto.
     * Si no hay límites configurados, permite envío ilimitado.
     */
    public static function forCampaign(Campaign $campaign): self
    {
        $limits = [];
        $perMinute = (int) config('campaign.rate_limit_per_minute', 0);
        $perHour = (int) config('campaign.rate_limit_per_hour', 0);

        if ($perMinute > 0) {
            $limits[] = new RateLimit($perMinute, 1, 'minute', 'Límite por campaña: '.$perMinute.'/minuto');
        }
        if ($perHour > 0) {
            $limits[] = new RateLimit($perHour, 1, 'hour', 'Límite por campaña: '.$perHour.'/hora');
        }

        return new self($campaign->uid, $limits);
    }

    public function count(?\DateTimeInterface $now = null, ?callable $rateExceedingCallback = null): int
    {
        if (empty($this->limits)) {
            return 0;
        }

        $now = $now ?? now();
        $counts = Cache::get($this->key, []);
        $total = 0;

        foreach ($this->limits as $limit) {
            $window = $this->windowKey($now, $limit);
            $current = $counts[$window] ?? 0;
            $total += $current;

            if ($current >= $limit->getAmount() && $rateExceedingCallback) {
                $rateExceedingCallback($limit);
            }
        }

        return $total;
    }

    public function test(?\DateTimeInterface $now = null, ?callable $rateExceedingCallback = null): void
    {
        if (empty($this->limits)) {
            return;
        }

        $now = $now ?? now();
        $counts = Cache::get($this->key, []);

        foreach ($this->limits as $limit) {
            $window = $this->windowKey($now, $limit);
            $current = $counts[$window] ?? 0;

            if ($current >= $limit->getAmount()) {
                if ($rateExceedingCallback) {
                    $rateExceedingCallback($limit);
                }
                throw new RateLimitExceeded($limit->getDescription());
            }
        }
    }

    public function increment(): void
    {
        if (empty($this->limits)) {
            return;
        }

        $now = now();
        $counts = Cache::get($this->key, []);

        foreach ($this->limits as $limit) {
            $window = $this->windowKey($now, $limit);
            $counts[$window] = ($counts[$window] ?? 0) + 1;
            $ttl = $this->ttlForLimit($limit);
            Cache::put($this->key, $counts, $ttl);
        }
    }

    public function rollback(): void
    {
        // No-op: los rate limits de campaña no se hacen rollback por simplicidad.
    }

    private function windowKey(\DateTimeInterface $now, RateLimit $limit): string
    {
        return match ($limit->getPeriodUnit()) {
            'minute' => $now->format('YmdHi'),
            'hour' => $now->format('YmdH'),
            'day' => $now->format('Ymd'),
            default => $now->format('YmdHi'),
        };
    }

    private function ttlForLimit(RateLimit $limit): int
    {
        return match ($limit->getPeriodUnit()) {
            'minute' => 120,
            'hour' => 7200,
            'day' => 172800,
            default => 120,
        };
    }
}
