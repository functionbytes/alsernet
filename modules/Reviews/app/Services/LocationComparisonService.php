<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;

class LocationComparisonService
{
    private const RATING_NUMERIC = "CASE reviews.star_rating
        WHEN 'ONE' THEN 1
        WHEN 'TWO' THEN 2
        WHEN 'THREE' THEN 3
        WHEN 'FOUR' THEN 4
        WHEN 'FIVE' THEN 5
        ELSE 0 END";

    /**
     * Compare metrics for the given location IDs over the specified period.
     *
     * @param  array<int>  $locationIds
     * @return array<int, array{location_id: int, location_name: string, avg_rating: float, total_reviews: int, response_rate: float, avg_response_time_hours: float, pending_replies: int}>
     */
    public function compare(array $locationIds, int $days = 30): array
    {
        if (empty($locationIds)) {
            return [];
        }

        $since = now()->subDays($days);

        $rows = Review::query()
            ->select([
                'reviews.location_id',
                'review_google_locations.name as location_name',
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('COALESCE(AVG('.self::RATING_NUMERIC.'), 0) as avg_rating'),
                DB::raw('SUM(CASE WHEN reviews.comment IS NOT NULL THEN 1 ELSE 0 END) as with_comment'),
                DB::raw('SUM(CASE WHEN reviews.google_reply_text IS NOT NULL AND reviews.comment IS NOT NULL THEN 1 ELSE 0 END) as replied'),
                DB::raw('SUM(CASE WHEN reviews.google_reply_text IS NULL AND reviews.comment IS NOT NULL THEN 1 ELSE 0 END) as pending_replies'),
                DB::raw('AVG(CASE WHEN reviews.google_reply_text IS NOT NULL AND reviews.google_reply_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, reviews.review_time, reviews.google_reply_time) / 60.0 ELSE NULL END) as avg_response_hours'),
            ])
            ->join('review_google_locations', 'reviews.location_id', '=', 'review_google_locations.id')
            ->whereIn('reviews.location_id', $locationIds)
            ->where('reviews.review_time', '>=', $since)
            ->groupBy('reviews.location_id', 'review_google_locations.name')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $withComment = (int) $row->with_comment;
            $responseRate = $withComment > 0
                ? round(((int) $row->replied / $withComment) * 100, 1)
                : 0.0;

            $result[$row->location_id] = [
                'location_id' => (int) $row->location_id,
                'location_name' => $row->location_name,
                'avg_rating' => round((float) $row->avg_rating, 2),
                'total_reviews' => (int) $row->total_reviews,
                'response_rate' => $responseRate,
                'avg_response_time_hours' => round((float) ($row->avg_response_hours ?? 0), 1),
                'pending_replies' => (int) $row->pending_replies,
            ];
        }

        // Include locations with zero reviews in the period
        foreach ($locationIds as $id) {
            if (isset($result[$id])) {
                continue;
            }

            $location = ReviewGoogleLocation::find($id);
            if ($location) {
                $result[$id] = [
                    'location_id' => $id,
                    'location_name' => $location->name,
                    'avg_rating' => 0.0,
                    'total_reviews' => 0,
                    'response_rate' => 0.0,
                    'avg_response_time_hours' => 0.0,
                    'pending_replies' => 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Return all locations for the given user, ranked by avg_rating then response_rate,
     * with rank position and delta vs the previous equivalent period.
     *
     * @return array<int, array{rank: int, location_id: int, location_name: string, avg_rating: float, total_reviews: int, response_rate: float, avg_response_time_hours: float, pending_replies: int, delta_rating: float}>
     */
    public function getRankings(int $userId, int $days = 30): array
    {
        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', $userId))
            ->pluck('id')
            ->toArray();

        if (empty($locationIds)) {
            return [];
        }

        $current = $this->compare($locationIds, $days);
        $previous = $this->compare($locationIds, $days * 2);

        // Sort by avg_rating DESC then response_rate DESC
        uasort($current, static function (array $a, array $b): int {
            if ($b['avg_rating'] !== $a['avg_rating']) {
                return $b['avg_rating'] <=> $a['avg_rating'];
            }

            return $b['response_rate'] <=> $a['response_rate'];
        });

        $ranked = [];
        $rank = 1;

        foreach ($current as $locId => $data) {
            $prevRating = $previous[$locId]['avg_rating'] ?? 0.0;
            $data['rank'] = $rank++;
            $data['delta_rating'] = round($data['avg_rating'] - $prevRating, 2);
            $ranked[] = $data;
        }

        return $ranked;
    }

    /**
     * Summarize best/worst performer and overall averages for the given locations.
     *
     * @param  array<int>  $locationIds
     * @return array{best: array|null, worst: array|null, overall_avg_rating: float, overall_response_rate: float}
     */
    public function getPerformanceSummary(array $locationIds, int $days = 30): array
    {
        $data = $this->compare($locationIds, $days);

        if (empty($data)) {
            return [
                'best' => null,
                'worst' => null,
                'overall_avg_rating' => 0.0,
                'overall_response_rate' => 0.0,
            ];
        }

        $withReviews = array_filter($data, fn ($d) => $d['total_reviews'] > 0);

        $best = null;
        $worst = null;

        foreach ($withReviews as $item) {
            if ($best === null || $item['avg_rating'] > $best['avg_rating']) {
                $best = $item;
            }
            if ($worst === null || $item['avg_rating'] < $worst['avg_rating']) {
                $worst = $item;
            }
        }

        $totalReviews = array_sum(array_column($data, 'total_reviews'));
        $overallRating = $totalReviews > 0
            ? round(array_sum(array_map(fn ($d) => $d['avg_rating'] * $d['total_reviews'], $data)) / $totalReviews, 2)
            : 0.0;

        $totalWithComment = 0;
        $totalReplied = 0;

        foreach ($data as $item) {
            $totalWithComment += (int) round($item['total_reviews'] * ($item['response_rate'] + $item['pending_replies'] > 0 ? 1 : 0));
        }

        // Simpler: weighted avg of response_rate
        $overallResponseRate = $totalReviews > 0
            ? round(array_sum(array_map(fn ($d) => $d['response_rate'] * $d['total_reviews'], $data)) / $totalReviews, 1)
            : 0.0;

        return [
            'best' => $best,
            'worst' => $worst,
            'overall_avg_rating' => $overallRating,
            'overall_response_rate' => $overallResponseRate,
        ];
    }
}
