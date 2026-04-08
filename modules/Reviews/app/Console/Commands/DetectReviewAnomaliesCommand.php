<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Events\ReviewAnomalyDetected;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\AnomalyDetectionService;

class DetectReviewAnomaliesCommand extends Command
{
    protected $signature = 'reviews:detect-anomalies {--window=24 : Window size in hours}';

    protected $description = 'Detect negative review spikes across all active locations and fire events when anomalies are found';

    public function __construct(private readonly AnomalyDetectionService $anomalyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $windowHours = (int) $this->option('window');

        $locationIds = ReviewGoogleLocation::query()
            ->active()
            ->whereHas('connection', fn ($q) => $q->active())
            ->pluck('id');

        if ($locationIds->isEmpty()) {
            $this->warn('No active locations found.');

            return self::SUCCESS;
        }

        $detected = 0;

        foreach ($locationIds as $locationId) {
            $anomaly = $this->anomalyService->detectNegativeSpike($locationId, $windowHours);

            if ($anomaly === null) {
                continue;
            }

            ReviewAnomalyDetected::dispatch($anomaly);
            $detected++;

            $this->warn(sprintf(
                'Anomaly at "%s": %d reviews (%.1fx normal rate)',
                $anomaly->locationName,
                $anomaly->currentCount,
                $anomaly->multiplier,
            ));
        }

        $this->info("Checked {$locationIds->count()} locations, detected {$detected} anomalies.");

        return self::SUCCESS;
    }
}
