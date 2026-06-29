<?php

namespace Modules\Reviews\Data;

use Carbon\Carbon;

readonly class AnomalyResult
{
    public function __construct(
        public int $locationId,
        public string $locationName,
        public int $currentCount,
        public float $historicalAverage,
        public float $multiplier,
        public Carbon $detectedAt,
    ) {}
}
