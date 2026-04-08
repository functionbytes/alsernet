<?php

namespace Modules\Analytics\Events;

use Modules\Analytics\Models\AnalyticsReportSchedule;

class ReportGenerated
{
    public function __construct(
        public readonly array $report,
        public readonly ?AnalyticsReportSchedule $schedule = null,
    ) {}
}
