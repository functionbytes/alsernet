<?php

namespace Modules\Analytics\Events;

use Modules\Analytics\Models\AnalyticsReportSchedule;

class ScheduleCreated
{
    public function __construct(
        public readonly AnalyticsReportSchedule $schedule,
    ) {}
}
