<?php

namespace Modules\Analytics\Traits;

use Modules\Analytics\Period;

trait DateRangeTrait
{
    protected array $dateRanges = [];

    public function dateRange(Period $period): self
    {
        $this->dateRanges = [
            [
                'start_date' => $period->startDate->format('Y-m-d'),
                'end_date' => $period->endDate->format('Y-m-d'),
            ],
        ];

        return $this;
    }
}
