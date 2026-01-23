<?php

namespace Modules\HelpdeskChat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Services\Sla\SlaTracker;

class CheckSlaBreach implements ShouldQueue
{
    use Queueable;

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
}
