<?php

namespace Modules\Reviews\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Reviews\Data\AnomalyResult;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;

class AnomalyDetectionService
{
    /** Multiplier threshold above which a spike is declared. */
    private const SPIKE_THRESHOLD = 3.0;

    /** Minimum historical average below which no spike is declared (avoid false positives on tiny samples). */
    private const MIN_HISTORICAL_AVERAGE = 0.5;

    /**
     * Detect a negative-review spike for a single location.
     *
     * Compares the count of 1–2 star reviews in the last $windowHours against
     * the average count for the same window length over the prior 30 days.
     * Returns null when no anomaly is detected.
     */
    public function detectNegativeSpike(int $locationId, int $windowHours = 24): ?AnomalyResult
    {
        $location = ReviewGoogleLocation::query()->find($locationId);

        if (! $location) {
            return null;
        }

        $currentCount = $this->countNegativeReviewsInWindow($locationId, $windowHours, now()->subHours($windowHours), now());
        $historicalAverage = $this->calculateHistoricalAverage($locationId, $windowHours);

        if ($historicalAverage < self::MIN_HISTORICAL_AVERAGE) {
            return null;
        }

        $multiplier = $currentCount / $historicalAverage;

        if ($multiplier < self::SPIKE_THRESHOLD) {
            return null;
        }

        return new AnomalyResult(
            locationId: $locationId,
            locationName: $location->name,
            currentCount: $currentCount,
            historicalAverage: round($historicalAverage, 2),
            multiplier: round($multiplier, 2),
            detectedAt: now(),
        );
    }

    /**
     * Returns anomaly results for all locations belonging to the given user over the last N days.
     *
     * @return AnomalyResult[]
     */
    public function getRecentAnomalies(int $userId, int $days = 7): array
    {
        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', $userId)->active())
            ->active()
            ->pluck('id');

        $anomalies = [];

        foreach ($locationIds as $locationId) {
            $result = $this->detectNegativeSpike($locationId);

            if ($result !== null) {
                $anomalies[] = $result;
            }
        }

        return $anomalies;
    }

    private function countNegativeReviewsInWindow(int $locationId, int $windowHours, Carbon $from, Carbon $to): int
    {
        return Review::query()
            ->where('location_id', $locationId)
            ->whereIn('star_rating', ['ONE', 'TWO'])
            ->whereBetween('review_time', [$from, $to])
            ->count();
    }

    private function calculateHistoricalAverage(int $locationId, int $windowHours): float
    {
        // Sample 30 non-overlapping windows of $windowHours each, starting from 2 windows ago
        $numberOfWindows = 30;
        $totalDaysBack = ($numberOfWindows + 1) * $windowHours / 24;

        $isUsingSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isUsingSqlite) {
            $windowExpr = "CAST((JULIANDAY(:end, 'start of day') - JULIANDAY(review_time)) * 24 / :hours AS INTEGER)";
        } else {
            $windowExpr = 'FLOOR(TIMESTAMPDIFF(HOUR, review_time, :end) / :hours)';
        }

        $windowEnd = now()->subHours($windowHours);

        $counts = Review::query()
            ->where('location_id', $locationId)
            ->whereIn('star_rating', ['ONE', 'TWO'])
            ->where('review_time', '>=', now()->subDays($totalDaysBack))
            ->where('review_time', '<', $windowEnd)
            ->selectRaw(
                "($windowExpr) as window_index, COUNT(*) as cnt",
                ['end' => $windowEnd->toDateTimeString(), 'hours' => $windowHours]
            )
            ->groupBy('window_index')
            ->having('window_index', '>=', 1)
            ->having('window_index', '<=', $numberOfWindows)
            ->pluck('cnt');

        if ($counts->isEmpty()) {
            return 0.0;
        }

        return $counts->sum() / $numberOfWindows;
    }
}
