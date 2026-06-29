<?php

namespace Modules\Analytics\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Analytics\Events\ReportGenerated;

class LogReportGenerated
{
    public function handle(ReportGenerated $event): void
    {
        Log::info('Analytics report generated', [
            'type' => $event->report['type'] ?? null,
            'schedule_id' => $event->schedule?->id,
        ]);
    }
}
