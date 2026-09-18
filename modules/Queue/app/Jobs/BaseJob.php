<?php

namespace Modules\Queue\Jobs;

use Modules\Core\Jobs\Base;
use Spatie\RateLimitedMiddleware\RateLimited;

abstract class BaseJob extends Base
{
    public $failOnTimeout = true;

    public $tries = 3;

    public $maxExceptions = 3;

    public $backoff = 30;

    /**
     * Horizon display tags for this job.
     */
    public function tags(): array
    {
        return [class_basename(static::class)];
    }

    /**
     * Helper to create a Spatie RateLimited middleware instance.
     *
     * Usage in a job's middleware() method:
     *   return [$this->throttle('google-sync', maxJobs: 5, perSeconds: 60)];
     */
    protected function throttle(string $key, int $maxJobs, int $perSeconds): RateLimited
    {
        return (new RateLimited)
            ->allow($maxJobs)
            ->everySeconds($perSeconds)
            ->key($key);
    }
}
