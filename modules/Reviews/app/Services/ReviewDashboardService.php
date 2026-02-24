<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reviews\Models\Review;

class ReviewDashboardService
{
    public function getKpiMetrics(): array
    {
        $totalReviews = Review::query()->count();

        $avgRating = Review::query()
            ->selectRaw('COALESCE(AVG(CAST(star_rating AS UNSIGNED)), 0) as avg')
            ->value('avg');

        $unanswered = Review::query()
            ->whereNull('google_reply_text')
            ->whereNotNull('comment')
            ->count();

        $totalReplied = Review::query()
            ->whereNotNull('google_reply_text')
            ->whereNotNull('comment')
            ->count();

        $totalWithComment = Review::query()
            ->whereNotNull('comment')
            ->count();

        $responseRate = $totalWithComment > 0
            ? round(($totalReplied / $totalWithComment) * 100, 1)
            : 0;

        return [
            'total' => $totalReviews,
            'avg_rating' => round($avgRating, 2),
            'unanswered' => $unanswered,
            'response_rate' => $responseRate,
        ];
    }

    public function getRatingTrends(int $months = 12): array
    {
        $results = Review::query()
            ->selectRaw("DATE_FORMAT(review_time, '%Y-%m') as month")
            ->selectRaw('COALESCE(AVG(CAST(star_rating AS UNSIGNED)), 0) as avg_rating')
            ->selectRaw('COUNT(*) as count')
            ->where('review_time', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $results->map(fn ($row) => [
            'month' => $row->month,
            'avg_rating' => round($row->avg_rating, 2),
            'count' => $row->count,
        ])->toArray();
    }

    public function getLocationStats(): array
    {
        $results = Review::query()
            ->select([
                'review_google_locations.id',
                'review_google_locations.name',
                DB::raw('COUNT(reviews.id) as review_count'),
                DB::raw('COALESCE(AVG(CAST(reviews.star_rating AS UNSIGNED)), 0) as avg_rating'),
            ])
            ->join('review_google_locations', 'reviews.location_id', '=', 'review_google_locations.id')
            ->groupBy('review_google_locations.id', 'review_google_locations.name')
            ->orderByDesc('review_count')
            ->limit(10)
            ->get();

        return $results->map(fn ($row) => [
            'location_id' => $row->id,
            'location_name' => $row->name,
            'review_count' => $row->review_count,
            'avg_rating' => round($row->avg_rating, 2),
        ])->toArray();
    }

    public function getReviewsByDay(int $days = 30): array
    {
        $results = Review::query()
            ->selectRaw("DATE_FORMAT(review_time, '%Y-%m-%d') as date")
            ->selectRaw('COUNT(*) as count')
            ->where('review_time', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $results->map(fn ($row) => [
            'date' => $row->date,
            'count' => $row->count,
        ])->toArray();
    }

    public function getRatingDistribution(): array
    {
        $results = Review::query()
            ->selectRaw('star_rating, COUNT(*) as count')
            ->groupBy('star_rating')
            ->get();

        $distribution = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        foreach ($results as $row) {
            $rating = (string) $row->star_rating;
            if (isset($distribution[$rating])) {
                $distribution[$rating] = $row->count;
            }
        }

        return $distribution;
    }
}
