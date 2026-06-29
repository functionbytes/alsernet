<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Reviews\Models\Review;

class ReviewReportService
{
    /**
     * Generate location summary report data.
     */
    public function generateLocationReport(array $filters = []): array
    {
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->subMonth();
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
        $locationIds = $filters['location_ids'] ?? null;

        $query = Review::query()
            ->whereBetween('review_time', [$dateFrom, $dateTo])
            ->with('location');

        if ($locationIds) {
            $query->whereIn('location_id', $locationIds);
        }

        $reviews = $query->limit(10000)->get();

        $locationStats = $reviews->groupBy('location_id')->map(function ($locationReviews) {
            $location = $locationReviews->first()->location;
            $total = $locationReviews->count();
            $withReply = $locationReviews->filter(fn ($r) => $r->hasGoogleReply())->count();

            return [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'total_reviews' => $total,
                'avg_rating' => round($locationReviews->avg(fn ($r) => $r->star_rating->value()), 2),
                'rating_distribution' => $this->getRatingDistribution($locationReviews),
                'with_comment' => $locationReviews->filter(fn ($r) => ! empty($r->comment))->count(),
                'with_reply' => $withReply,
                'reply_rate' => $total > 0 ? round(($withReply / $total) * 100, 2) : 0,
            ];
        })->values()->toArray();

        return [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
            ],
            'summary' => [
                'total_reviews' => $reviews->count(),
                'avg_rating' => round($reviews->avg(fn ($r) => $r->star_rating->value()), 2),
                'total_locations' => count($locationStats),
            ],
            'locations' => $locationStats,
        ];
    }

    /**
     * Generate period analysis report data.
     */
    public function generatePeriodReport(array $filters = []): array
    {
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->subMonth();
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
        $locationIds = $filters['location_ids'] ?? null;
        $groupBy = $filters['group_by'] ?? 'day'; // day, week, month

        $query = Review::query()
            ->whereBetween('review_time', [$dateFrom, $dateTo]);

        if ($locationIds) {
            $query->whereIn('location_id', $locationIds);
        }

        $reviews = $query->orderBy('review_time')->limit(10000)->get();

        $timeSeriesData = $this->groupByTimePeriod($reviews, $groupBy);

        return [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
                'group_by' => $groupBy,
            ],
            'summary' => [
                'total_reviews' => $reviews->count(),
                'avg_rating' => round($reviews->avg(fn ($r) => $r->star_rating->value()), 2),
                'avg_per_period' => count($timeSeriesData) > 0 ? round($reviews->count() / count($timeSeriesData), 2) : 0,
            ],
            'time_series' => $timeSeriesData,
            'rating_distribution' => $this->getRatingDistribution($reviews),
        ];
    }

    /**
     * Generate comparison report between two periods.
     */
    public function generateComparisonReport(array $filters = []): array
    {
        $period1From = isset($filters['period1_from']) ? Carbon::parse($filters['period1_from']) : now()->subMonths(2);
        $period1To = isset($filters['period1_to']) ? Carbon::parse($filters['period1_to']) : now()->subMonth();
        $period2From = isset($filters['period2_from']) ? Carbon::parse($filters['period2_from']) : now()->subMonth();
        $period2To = isset($filters['period2_to']) ? Carbon::parse($filters['period2_to']) : now();
        $locationIds = $filters['location_ids'] ?? null;

        $period1Reviews = $this->getReviewsForPeriod($period1From, $period1To, $locationIds);
        $period2Reviews = $this->getReviewsForPeriod($period2From, $period2To, $locationIds);

        $period1Stats = $this->calculatePeriodStats($period1Reviews);
        $period2Stats = $this->calculatePeriodStats($period2Reviews);

        return [
            'period1' => [
                'from' => $period1From->toDateString(),
                'to' => $period1To->toDateString(),
                'stats' => $period1Stats,
            ],
            'period2' => [
                'from' => $period2From->toDateString(),
                'to' => $period2To->toDateString(),
                'stats' => $period2Stats,
            ],
            'comparison' => [
                'reviews_change' => $this->calculateChange($period1Stats['total_reviews'], $period2Stats['total_reviews']),
                'rating_change' => round($period2Stats['avg_rating'] - $period1Stats['avg_rating'], 2),
                'reply_rate_change' => round($period2Stats['reply_rate'] - $period1Stats['reply_rate'], 2),
            ],
        ];
    }

    /**
     * Generate response performance report data.
     */
    public function generateResponsePerformanceReport(array $filters = []): array
    {
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->subMonth();
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
        $locationIds = $filters['location_ids'] ?? null;

        $query = Review::query()
            ->whereBetween('review_time', [$dateFrom, $dateTo]);

        if ($locationIds) {
            $query->whereIn('location_id', $locationIds);
        }

        $reviews = $query->limit(10000)->get();
        $withReply = $reviews->filter(fn ($r) => $r->hasGoogleReply());
        $withoutReply = $reviews->filter(fn ($r) => ! $r->hasGoogleReply());

        $avgResponseTime = $withReply->map(function ($review) {
            if (! $review->google_reply_time) {
                return null;
            }

            return $review->review_time->diffInHours($review->google_reply_time);
        })->filter()->avg();

        return [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
            ],
            'summary' => [
                'total_reviews' => $reviews->count(),
                'with_reply' => $withReply->count(),
                'without_reply' => $withoutReply->count(),
                'reply_rate' => $reviews->count() > 0 ? round(($withReply->count() / $reviews->count()) * 100, 2) : 0,
                'avg_response_time_hours' => $avgResponseTime ? round($avgResponseTime, 2) : null,
            ],
            'by_rating' => $this->getResponseRateByRating($reviews),
        ];
    }

    /**
     * Prepare detailed export data for CSV/Excel.
     */
    public function generateExportReport(array $filters = []): Collection
    {
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->subMonth();
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
        $locationIds = $filters['location_ids'] ?? null;

        $query = Review::query()
            ->with(['location', 'moderation'])
            ->whereBetween('review_time', [$dateFrom, $dateTo]);

        if ($locationIds) {
            $query->whereIn('location_id', $locationIds);
        }

        return $query->orderBy('review_time', 'desc')->limit(10000)->get();
    }

    /**
     * Generate a monthly PDF report for a user, save it to storage and return the path.
     *
     * @return string Relative storage path (storage/app/...)
     */
    public function generateMonthlyPdf(User $user, int $year, int $month, ?int $locationId = null): string
    {
        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $query = Review::query()
            ->with('location:id,name')
            ->whereBetween('review_time', [$from, $to]);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        $reviews = $query->limit(10000)->get();

        $total = $reviews->count();
        $avgRating = $total > 0 ? round($reviews->avg(fn ($r) => $r->star_rating->value()), 2) : 0.0;
        $withComment = $reviews->filter(fn ($r) => ! empty($r->comment))->count();
        $replied = $reviews->filter(fn ($r) => $r->hasGoogleReply())->count();
        $responseRate = $withComment > 0 ? round(($replied / $withComment) * 100, 1) : 0.0;
        $pendingReplies = $reviews->filter(fn ($r) => ! $r->hasGoogleReply() && ! empty($r->comment))->count();

        $ratingDistribution = $this->getRatingDistribution($reviews);

        $topPositive = $reviews
            ->filter(fn ($r) => $r->star_rating->value() >= 4 && ! empty($r->comment))
            ->sortByDesc(fn ($r) => $r->star_rating->value())
            ->take(5)
            ->map(fn ($r) => [
                'rating' => $r->star_rating->value(),
                'reviewer_name' => $r->reviewer_name ?? 'Anonimo',
                'location_name' => $r->location?->name ?? 'Sin ubicacion',
                'review_date' => $r->review_time?->format('d/m/Y'),
                'comment' => mb_substr($r->comment, 0, 150),
            ])->values()->toArray();

        $topNegative = $reviews
            ->filter(fn ($r) => $r->star_rating->value() <= 2 && ! empty($r->comment) && ! $r->hasGoogleReply())
            ->sortBy(fn ($r) => $r->star_rating->value())
            ->take(5)
            ->map(fn ($r) => [
                'rating' => $r->star_rating->value(),
                'reviewer_name' => $r->reviewer_name ?? 'Anonimo',
                'location_name' => $r->location?->name ?? 'Sin ubicacion',
                'review_date' => $r->review_time?->format('d/m/Y'),
                'comment' => mb_substr($r->comment, 0, 150),
            ])->values()->toArray();

        $withReply = $reviews->filter(fn ($r) => $r->hasGoogleReply());
        $avgResponseHours = $withReply->map(function ($r) {
            if (! $r->google_reply_time || ! $r->review_time) {
                return null;
            }

            return $r->review_time->diffInHours($r->google_reply_time);
        })->filter()->avg();

        $avgResponseHours = $avgResponseHours !== null ? round((float) $avgResponseHours, 1) : null;
        $avgResponseFormatted = match (true) {
            $avgResponseHours === null => 'Sin datos',
            $avgResponseHours > 48 => round($avgResponseHours / 24, 1).' dias',
            default => $avgResponseHours.' horas',
        };

        $locationName = $locationId !== null
            ? ($reviews->first()?->location?->name ?? null)
            : null;

        $data = [
            'period' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            'periodFrom' => $from->format('d/m/Y'),
            'periodTo' => $to->format('d/m/Y'),
            'locationName' => $locationName,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'summary' => [
                'total_reviews' => $total,
                'avg_rating' => $avgRating,
                'response_rate' => $responseRate,
                'pending_replies' => $pendingReplies,
            ],
            'ratingDistribution' => $ratingDistribution,
            'topPositive' => $topPositive,
            'topNegative' => $topNegative,
            'responsePerformance' => [
                'with_comment' => $withComment,
                'replied' => $replied,
                'response_rate' => $responseRate,
                'avg_response_time_formatted' => $avgResponseFormatted,
                'without_reply' => $pendingReplies,
            ],
        ];

        $pdf = Pdf::loadView('reviews::reports.monthly-pdf', $data);
        $pdf->setPaper('A4');

        $relativePath = "reports/monthly_{$user->id}_{$year}_{$month}.pdf";
        Storage::disk('local')->put($relativePath, $pdf->output());

        return $relativePath;
    }

    /**
     * Get rating distribution from reviews collection.
     */
    private function getRatingDistribution(Collection $reviews): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($reviews as $review) {
            $rating = $review->star_rating->value();
            $distribution[$rating] = ($distribution[$rating] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Group reviews by time period.
     */
    private function groupByTimePeriod(Collection $reviews, string $groupBy): array
    {
        $grouped = $reviews->groupBy(function ($review) use ($groupBy) {
            return match ($groupBy) {
                'week' => $review->review_time->startOfWeek()->toDateString(),
                'month' => $review->review_time->startOfMonth()->toDateString(),
                default => $review->review_time->toDateString(),
            };
        });

        return $grouped->map(function ($periodReviews, $period) {
            return [
                'period' => $period,
                'total_reviews' => $periodReviews->count(),
                'avg_rating' => round($periodReviews->avg(fn ($r) => $r->star_rating->value()), 2),
                'with_reply' => $periodReviews->filter(fn ($r) => $r->hasGoogleReply())->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Get reviews for a specific period.
     */
    private function getReviewsForPeriod(Carbon $from, Carbon $to, ?array $locationIds): Collection
    {
        $query = Review::query()
            ->whereBetween('review_time', [$from, $to]);

        if ($locationIds) {
            $query->whereIn('location_id', $locationIds);
        }

        return $query->limit(10000)->get();
    }

    /**
     * Calculate statistics for a period.
     */
    private function calculatePeriodStats(Collection $reviews): array
    {
        $total = $reviews->count();
        $withReply = $reviews->filter(fn ($r) => $r->hasGoogleReply())->count();

        return [
            'total_reviews' => $total,
            'avg_rating' => round($reviews->avg(fn ($r) => $r->star_rating->value()), 2),
            'with_comment' => $reviews->filter(fn ($r) => ! empty($r->comment))->count(),
            'with_reply' => $withReply,
            'reply_rate' => $total > 0 ? round(($withReply / $total) * 100, 2) : 0,
            'rating_distribution' => $this->getRatingDistribution($reviews),
        ];
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculateChange(float $oldValue, float $newValue): float
    {
        if ($oldValue === 0.0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Get response rate by rating.
     */
    private function getResponseRateByRating(Collection $reviews): array
    {
        $byRating = [];

        for ($rating = 1; $rating <= 5; $rating++) {
            $ratingReviews = $reviews->filter(fn ($r) => $r->star_rating->value() === $rating);
            $total = $ratingReviews->count();
            $withReply = $ratingReviews->filter(fn ($r) => $r->hasGoogleReply())->count();

            $byRating[$rating] = [
                'total' => $total,
                'with_reply' => $withReply,
                'reply_rate' => $total > 0 ? round(($withReply / $total) * 100, 2) : 0,
            ];
        }

        return $byRating;
    }
}
