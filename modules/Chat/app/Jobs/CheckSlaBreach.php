<?php

namespace Modules\Chat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Services\Sla\SlaTracker;

class CheckSlaBreach implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Execute the job.
     */
    public function handle(SlaTracker $slaTracker): void
    {
        $breachCount = $slaTracker->checkBreaches();

        if ($breachCount > 0) {
            Log::info("SLA Breach Check: {$breachCount} new breaches detected");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
