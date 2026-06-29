<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;

class ReviewDashboardService
{
    /**
     * SQL expression to convert enum star_rating to its numeric value.
     * The enum stores values as strings: ONE, TWO, THREE, FOUR, FIVE.
     */
    private const RATING_NUMERIC = "CASE star_rating
        WHEN 'ONE' THEN 1
        WHEN 'TWO' THEN 2
        WHEN 'THREE' THEN 3
        WHEN 'FOUR' THEN 4
        WHEN 'FIVE' THEN 5
        ELSE 0 END";

    /** Cache TTL for aggregate metrics that do not need to be real-time (2 hours). */
    private const TTL = 7200;

    /** Cache TTL for time-sensitive metrics like pending reply counts (15 min). */
    private const REALTIME_TTL = 900;

    // -------------------------------------------------------------------------
    // Individual metric methods (required public API)
    // -------------------------------------------------------------------------

    public function getTotalReviews(): int
    {
        return Cache::remember('reviews.total_count', self::TTL, fn () => Review::query()->count());
    }

    public function getAverageRating(): float
    {
        return Cache::remember('reviews.avg_rating', self::TTL, function () {
            $avg = Review::query()
                ->selectRaw('COALESCE(AVG('.self::RATING_NUMERIC.'), 0) as avg')
                ->value('avg');

            return round((float) $avg, 2);
        });
    }

    public function getPendingReplies(): int
    {
        return Cache::remember('reviews.pending_replies', self::REALTIME_TTL, fn () => Review::query()
            ->whereNull('google_reply_text')
            ->whereNotNull('comment')
            ->count());
    }

    public function getResponseRate(): float
    {
        return Cache::remember('reviews.response_rate', self::TTL, function () {
            $row = Review::query()->selectRaw('
                SUM(CASE WHEN comment IS NOT NULL THEN 1 ELSE 0 END) as with_comment,
                SUM(CASE WHEN google_reply_text IS NOT NULL AND comment IS NOT NULL THEN 1 ELSE 0 END) as replied
            ')->first();

            $withComment = (int) ($row->with_comment ?? 0);

            if ($withComment === 0) {
                return 0.0;
            }

            return round(((int) ($row->replied ?? 0) / $withComment) * 100, 1);
        });
    }

    /**
     * @return array<int, int> [1 => count, 2 => count, ..., 5 => count]
     */
    public function getRatingDistribution(): array
    {
        return Cache::remember('reviews.rating_distribution', self::TTL, function () {
            $results = Review::query()
                ->selectRaw('star_rating, COUNT(*) as count')
                ->groupBy('star_rating')
                ->get();

            $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $map = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];

            foreach ($results as $row) {
                $key = $row->star_rating instanceof \BackedEnum ? $row->star_rating->value : (string) $row->star_rating;
                $numeric = $map[$key] ?? null;
                if ($numeric !== null) {
                    $distribution[$numeric] = (int) $row->count;
                }
            }

            return $distribution;
        });
    }

    /**
     * Reviews grouped by day for the last N days.
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function getRecentTrend(int $days = 30): array
    {
        return Cache::remember("reviews.recent_trend.$days", self::TTL, function () use ($days) {
            $dateExpr = $this->isUsingSqlite()
                ? "strftime('%Y-%m-%d', review_time) as date"
                : "DATE_FORMAT(review_time, '%Y-%m-%d') as date";

            return Review::query()
                ->selectRaw("$dateExpr, COUNT(*) as count")
                ->where('review_time', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
                ->toArray();
        });
    }

    /**
     * @return array<int, array{location_id: int, location_name: string, review_count: int, avg_rating: float}>
     */
    public function getTopLocations(int $limit = 5): array
    {
        return Cache::remember("reviews.top_locations.$limit", self::TTL, function () use ($limit) {
            return Review::query()
                ->select([
                    'review_google_locations.id',
                    'review_google_locations.name',
                    DB::raw('COUNT(reviews.id) as review_count'),
                    DB::raw('COALESCE(AVG('.self::RATING_NUMERIC.'), 0) as avg_rating'),
                ])
                ->join('review_google_locations', 'reviews.location_id', '=', 'review_google_locations.id')
                ->groupBy('review_google_locations.id', 'review_google_locations.name')
                ->orderByDesc('review_count')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => [
                    'location_id' => $row->id,
                    'location_name' => $row->name,
                    'review_count' => (int) $row->review_count,
                    'avg_rating' => round((float) $row->avg_rating, 2),
                ])
                ->toArray();
        });
    }

    // -------------------------------------------------------------------------
    // Composite / dashboard methods
    // -------------------------------------------------------------------------

    public function getKpiMetrics(): array
    {
        $aggregate = Cache::remember('reviews.kpi_aggregate', self::REALTIME_TTL, function () {
            $row = Review::query()->selectRaw('
                COUNT(*) as total_reviews,
                COALESCE(AVG('.self::RATING_NUMERIC.'), 0) as avg_rating,
                SUM(CASE WHEN google_reply_text IS NULL AND comment IS NOT NULL THEN 1 ELSE 0 END) as pending_replies,
                SUM(CASE WHEN comment IS NOT NULL THEN 1 ELSE 0 END) as with_comment,
                SUM(CASE WHEN google_reply_text IS NOT NULL AND comment IS NOT NULL THEN 1 ELSE 0 END) as replied
            ')->first();

            $withComment = (int) ($row->with_comment ?? 0);
            $responseRate = $withComment > 0
                ? round(((int) ($row->replied ?? 0) / $withComment) * 100, 1)
                : 0.0;

            return [
                'total' => (int) ($row->total_reviews ?? 0),
                'avg_rating' => round((float) ($row->avg_rating ?? 0), 2),
                'unanswered' => (int) ($row->pending_replies ?? 0),
                'response_rate' => $responseRate,
            ];
        });

        return $aggregate;
    }

    public function getRatingTrends(int $months = 12): array
    {
        return Cache::remember("reviews.rating_trends.$months", self::TTL, function () use ($months) {
            $monthExpr = $this->isUsingSqlite()
                ? "strftime('%Y-%m', review_time) as month"
                : "DATE_FORMAT(review_time, '%Y-%m') as month";

            return Review::query()
                ->selectRaw($monthExpr)
                ->selectRaw('COALESCE(AVG('.self::RATING_NUMERIC.'), 0) as avg_rating')
                ->selectRaw('COUNT(*) as count')
                ->where('review_time', '>=', now()->subMonths($months))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'avg_rating' => round((float) $row->avg_rating, 2),
                    'count' => (int) $row->count,
                ])
                ->toArray();
        });
    }

    public function getLocationStats(): array
    {
        return $this->getTopLocations(10);
    }

    /**
     * @deprecated Use getRecentTrend() instead.
     */
    public function getReviewsByDay(int $days = 30): array
    {
        return $this->getRecentTrend($days);
    }

    public function getRecentReviews(int $limit = 10): array
    {
        return Review::query()
            ->with(['location:id,name'])
            ->orderByDesc('review_time')
            ->limit($limit)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'reviewer_name' => $review->reviewer_name ?? 'Anónimo',
                'location_name' => $review->location?->name ?? 'Sin ubicación',
                'star_rating' => $review->star_rating,
                'comment' => $review->comment ? substr($review->comment, 0, 100) : null,
                'review_time' => $review->review_time?->format('d/m/Y H:i'),
                'has_reply' => $review->hasGoogleReply(),
            ])
            ->toArray();
    }

    public function getReviewsNeedingAttention(int $limit = 10): array
    {
        $lowRatings = ['ONE', 'TWO', 'THREE'];

        return Review::query()
            ->with(['location:id,name'])
            ->whereNull('google_reply_text')
            ->whereNotNull('comment')
            ->where(function ($query) use ($lowRatings) {
                $query->whereIn('star_rating', $lowRatings)
                    ->orWhereRaw('LENGTH(comment) > 100');
            })
            ->orderByDesc('review_time')
            ->limit($limit)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'reviewer_name' => $review->reviewer_name ?? 'Anónimo',
                'location_name' => $review->location?->name ?? 'Sin ubicación',
                'star_rating' => $review->star_rating,
                'comment' => substr($review->comment, 0, 150),
                'review_time' => $review->review_time?->format('d/m/Y H:i'),
                'priority' => in_array($review->star_rating, [ReviewRating::ONE, ReviewRating::TWO], true) ? 'high' : 'medium',
            ])
            ->toArray();
    }

    public function getAverageResponseTime(): array
    {
        return Cache::remember('reviews.avg_response_time', self::TTL, function () {
            $diffExpr = $this->isUsingSqlite()
                ? 'AVG((JULIANDAY(google_reply_time) - JULIANDAY(review_time)) * 24) as avg_hours'
                : 'AVG(TIMESTAMPDIFF(HOUR, review_time, google_reply_time)) as avg_hours';

            $result = Review::query()
                ->whereNotNull('google_reply_text')
                ->whereNotNull('google_reply_time')
                ->whereNotNull('review_time')
                ->selectRaw($diffExpr)
                ->first();

            $avgHours = round((float) ($result->avg_hours ?? 0), 1);

            return [
                'hours' => $avgHours,
                'formatted' => $avgHours > 24
                    ? round($avgHours / 24, 1).' días'
                    : $avgHours.' horas',
            ];
        });
    }

    public function getTopReviewers(int $limit = 5): array
    {
        return Cache::remember("reviews.top_reviewers.$limit", self::TTL, fn () => Review::query()
            ->selectRaw('reviewer_name, COUNT(*) as review_count')
            ->whereNotNull('reviewer_name')
            ->groupBy('reviewer_name')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->reviewer_name,
                'count' => (int) $row->review_count,
            ])
            ->toArray());
    }

    /**
     * Top keywords from review comments for a location, cached for 2 hours.
     *
     * @return array<int, array{word: string, count: int, sentiment: string}>
     */
    public function getTopKeywords(?int $locationId = null, int $days = 30): array
    {
        $cacheKey = "reviews.keywords.{$locationId}.{$days}";

        return Cache::remember($cacheKey, self::TTL, function () use ($locationId, $days) {
            if ($locationId === null) {
                return [];
            }

            return app(KeywordExtractionService::class)->extractKeywords($locationId, $days);
        });
    }

    /**
     * Sentiment percentage distribution for a location, cached for 2 hours.
     *
     * @return array{positive: float, neutral: float, negative: float}
     */
    public function getSentimentDistribution(?int $locationId = null): array
    {
        $cacheKey = "reviews.sentiment_distribution.{$locationId}";

        return Cache::remember($cacheKey, self::TTL, fn () => app(SentimentAnalysisService::class)->getSentimentDistribution($locationId));
    }

    /**
     * Daily review counts plus a 7-day moving average for the given period.
     *
     * @return array{dates: list<string>, counts: list<int>, moving_avg_7d: list<float>}
     */
    public function getReviewVelocity(?int $locationId = null, int $days = 90): array
    {
        $cacheKey = "reviews.velocity.{$locationId}.{$days}";

        return Cache::remember($cacheKey, self::TTL, function () use ($locationId, $days) {
            $dateExpr = $this->isUsingSqlite()
                ? "strftime('%Y-%m-%d', review_time) as date"
                : "DATE_FORMAT(review_time, '%Y-%m-%d') as date";

            $query = Review::query()
                ->selectRaw("$dateExpr, COUNT(*) as count")
                ->where('review_time', '>=', now()->subDays($days));

            if ($locationId !== null) {
                $query->where('location_id', $locationId);
            }

            $rows = $query->groupBy('date')->orderBy('date')->get()->keyBy('date');

            // Build a continuous date range
            $dates = [];
            $counts = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $day = now()->subDays($i)->format('Y-m-d');
                $dates[] = $day;
                $counts[] = isset($rows[$day]) ? (int) $rows[$day]->count : 0;
            }

            // 7-day moving average
            $movingAvg = [];
            $window = 7;
            foreach ($counts as $index => $value) {
                $start = max(0, $index - $window + 1);
                $slice = array_slice($counts, $start, $index - $start + 1);
                $movingAvg[] = round(array_sum($slice) / count($slice), 2);
            }

            return ['dates' => $dates, 'counts' => $counts, 'moving_avg_7d' => $movingAvg];
        });
    }

    /**
     * Compare current 30-day review count vs previous 30-day period.
     *
     * @return array{current_period: int, previous_period: int, change_pct: float, trend: string}
     */
    public function getVelocityTrend(?int $locationId = null): array
    {
        $cacheKey = "reviews.velocity_trend.{$locationId}";

        return Cache::remember($cacheKey, self::TTL, function () use ($locationId) {
            $query = fn () => Review::query()
                ->when($locationId !== null, fn ($q) => $q->where('location_id', $locationId));

            $current = (int) $query()
                ->where('review_time', '>=', now()->subDays(30))
                ->count();

            $previous = (int) $query()
                ->where('review_time', '>=', now()->subDays(60))
                ->where('review_time', '<', now()->subDays(30))
                ->count();

            $changePct = $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : ($current > 0 ? 100.0 : 0.0);

            $trend = match (true) {
                $changePct > 0 => 'up',
                $changePct < 0 => 'down',
                default => 'flat',
            };

            return [
                'current_period' => $current,
                'previous_period' => $previous,
                'change_pct' => $changePct,
                'trend' => $trend,
            ];
        });
    }

    /**
     * Review count grouped by day of week.
     *
     * @return array<string, int> e.g. ['Monday' => 15, 'Tuesday' => 8, ...]
     */
    public function getPeakDays(?int $locationId = null): array
    {
        $cacheKey = "reviews.peak_days.{$locationId}";

        return Cache::remember($cacheKey, self::TTL, function () use ($locationId) {
            $dayExpr = $this->isUsingSqlite()
                ? "CASE CAST(strftime('%w', review_time) AS INTEGER)
                    WHEN 0 THEN 'Sunday' WHEN 1 THEN 'Monday' WHEN 2 THEN 'Tuesday'
                    WHEN 3 THEN 'Wednesday' WHEN 4 THEN 'Thursday' WHEN 5 THEN 'Friday'
                    ELSE 'Saturday' END as day_name"
                : 'DAYNAME(review_time) as day_name';

            $rows = Review::query()
                ->selectRaw("$dayExpr, COUNT(*) as count")
                ->when($locationId !== null, fn ($q) => $q->where('location_id', $locationId))
                ->whereNotNull('review_time')
                ->groupBy('day_name')
                ->get();

            $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $result = array_fill_keys($order, 0);

            foreach ($rows as $row) {
                if (isset($result[$row->day_name])) {
                    $result[$row->day_name] = (int) $row->count;
                }
            }

            return $result;
        });
    }

    private function isUsingSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    public function getSentimentTrend(int $days = 30): array
    {
        return Cache::remember("reviews.sentiment_trend.$days", self::TTL, function () use ($days) {
            $dateExpr = $this->isUsingSqlite()
                ? "strftime('%Y-%m-%d', review_time) as date"
                : "DATE_FORMAT(review_time, '%Y-%m-%d') as date";

            return Review::query()
                ->selectRaw($dateExpr)
                ->selectRaw("SUM(CASE WHEN star_rating IN ('FOUR','FIVE') THEN 1 ELSE 0 END) as positive")
                ->selectRaw("SUM(CASE WHEN star_rating = 'THREE' THEN 1 ELSE 0 END) as neutral")
                ->selectRaw("SUM(CASE WHEN star_rating IN ('ONE','TWO') THEN 1 ELSE 0 END) as negative")
                ->where('review_time', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'positive' => (int) $row->positive,
                    'neutral' => (int) $row->neutral,
                    'negative' => (int) $row->negative,
                ])
                ->toArray();
        });
    }
}
