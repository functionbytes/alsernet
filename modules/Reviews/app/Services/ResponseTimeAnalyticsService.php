<?php

namespace Modules\Reviews\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Reviews\Models\ReviewReply;

class ResponseTimeAnalyticsService
{
    /**
     * TIMESTAMPDIFF expression returning hours as a float.
     * Uses review_replies.published_at vs reviews.created_at per spec.
     */
    private const RESPONSE_HOURS = 'TIMESTAMPDIFF(MINUTE, reviews.review_time, review_replies.published_at) / 60.0';

    /**
     * @return array{
     *     avg_response_time_hours: float,
     *     median_response_time_hours: float,
     *     fastest_response_hours: float,
     *     slowest_response_hours: float,
     *     response_time_distribution: array<string, int>,
     *     response_time_trend: array<int, array{date: string, avg_hours: float}>
     * }
     */
    public function getResponseTimeStats(?int $locationId = null, int $days = 30): array
    {
        $since = now()->subDays($days);

        $query = ReviewReply::query()
            ->join('reviews', 'review_replies.review_id', '=', 'reviews.id')
            ->where('review_replies.status', 'published')
            ->whereNotNull('review_replies.published_at')
            ->whereNotNull('reviews.review_time')
            ->where('reviews.review_time', '>=', $since)
            ->whereRaw(self::RESPONSE_HOURS.' >= 0');

        if ($locationId !== null) {
            $query->where('reviews.location_id', $locationId);
        }

        // Aggregate stats
        $stats = (clone $query)
            ->selectRaw('
                AVG('.self::RESPONSE_HOURS.') as avg_hours,
                MIN('.self::RESPONSE_HOURS.') as fastest,
                MAX('.self::RESPONSE_HOURS.') as slowest,
                COUNT(*) as total
            ')
            ->first();

        $avgHours = round((float) ($stats->avg_hours ?? 0), 2);
        $fastestHours = round((float) ($stats->fastest ?? 0), 2);
        $slowestHours = round((float) ($stats->slowest ?? 0), 2);
        $total = (int) ($stats->total ?? 0);

        // Distribution buckets
        $distribution = $this->getDistribution(clone $query);

        // Median via percentile approximation
        $medianHours = $this->getMedian(clone $query, $total);

        // Trend per day
        $trend = $this->getTrend(clone $query);

        return [
            'avg_response_time_hours' => $avgHours,
            'median_response_time_hours' => $medianHours,
            'fastest_response_hours' => $fastestHours,
            'slowest_response_hours' => $slowestHours,
            'response_time_distribution' => $distribution,
            'response_time_trend' => $trend,
        ];
    }

    /**
     * @return array<int, array{user_id: int, user_name: string, avg_hours: float, reply_count: int}>
     */
    public function getResponseTimeByUser(?int $locationId = null, int $days = 30): array
    {
        $since = now()->subDays($days);

        $query = ReviewReply::query()
            ->join('reviews', 'review_replies.review_id', '=', 'reviews.id')
            ->join('users', 'review_replies.created_by', '=', 'users.id')
            ->where('review_replies.status', 'published')
            ->whereNotNull('review_replies.published_at')
            ->whereNotNull('reviews.review_time')
            ->where('reviews.review_time', '>=', $since)
            ->whereRaw(self::RESPONSE_HOURS.' >= 0')
            ->selectRaw('
                review_replies.created_by as user_id,
                CONCAT(IFNULL(users.firstname, \'\'), \' \', IFNULL(users.lastname, \'\')) as user_name,
                AVG('.self::RESPONSE_HOURS.') as avg_hours,
                COUNT(*) as reply_count
            ')
            ->groupBy('review_replies.created_by', 'users.firstname', 'users.lastname')
            ->orderByRaw('AVG('.self::RESPONSE_HOURS.')');

        if ($locationId !== null) {
            $query->where('reviews.location_id', $locationId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'user_name' => $row->user_name,
                'avg_hours' => round((float) $row->avg_hours, 2),
                'reply_count' => (int) $row->reply_count,
            ])
            ->toArray();
    }

    /**
     * Returns avg response time per day of week.
     *
     * @return array<int, array{day: string, avg_hours: float, reply_count: int}>
     */
    public function getResponseTimeByDayOfWeek(?int $locationId = null): array
    {
        $query = ReviewReply::query()
            ->join('reviews', 'review_replies.review_id', '=', 'reviews.id')
            ->where('review_replies.status', 'published')
            ->whereNotNull('review_replies.published_at')
            ->whereNotNull('reviews.review_time')
            ->whereRaw(self::RESPONSE_HOURS.' >= 0')
            ->selectRaw('
                DAYOFWEEK(reviews.review_time) as dow_num,
                AVG('.self::RESPONSE_HOURS.') as avg_hours,
                COUNT(*) as reply_count
            ')
            ->groupBy('dow_num')
            ->orderBy('dow_num');

        if ($locationId !== null) {
            $query->where('reviews.location_id', $locationId);
        }

        // DAYOFWEEK: 1=Sunday, 2=Monday, ..., 7=Saturday
        $dayNames = [1 => 'Dom', 2 => 'Lun', 3 => 'Mar', 4 => 'Mié', 5 => 'Jue', 6 => 'Vie', 7 => 'Sáb'];
        $byDow = [];

        foreach ($query->get() as $row) {
            $byDow[(int) $row->dow_num] = [
                'day' => $dayNames[(int) $row->dow_num] ?? '?',
                'avg_hours' => round((float) $row->avg_hours, 2),
                'reply_count' => (int) $row->reply_count,
            ];
        }

        // Fill missing days with zeros in Mon-Sun order (2-7, 1)
        $result = [];
        $order = [2, 3, 4, 5, 6, 7, 1];

        foreach ($order as $dow) {
            $result[] = $byDow[$dow] ?? [
                'day' => $dayNames[$dow] ?? '?',
                'avg_hours' => 0.0,
                'reply_count' => 0,
            ];
        }

        return $result;
    }

    /**
     * Format hours as a human-readable string: "2h 15m".
     */
    public static function formatHours(float $hours): string
    {
        if ($hours <= 0) {
            return '—';
        }

        $totalMinutes = (int) round($hours * 60);

        if ($totalMinutes < 60) {
            return $totalMinutes.'m';
        }

        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        if ($h >= 24) {
            $days = intdiv($h, 24);
            $remHours = $h % 24;

            return $remHours > 0 ? "{$days}d {$remHours}h" : "{$days}d";
        }

        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    /**
     * @param  Builder  $query
     * @return array<string, int>
     */
    private function getDistribution($query): array
    {
        $rows = (clone $query)
            ->selectRaw('
                SUM(CASE WHEN '.self::RESPONSE_HOURS.' < 1 THEN 1 ELSE 0 END) as under_1h,
                SUM(CASE WHEN '.self::RESPONSE_HOURS.' >= 1 AND '.self::RESPONSE_HOURS.' < 4 THEN 1 ELSE 0 END) as h1_4h,
                SUM(CASE WHEN '.self::RESPONSE_HOURS.' >= 4 AND '.self::RESPONSE_HOURS.' < 24 THEN 1 ELSE 0 END) as h4_24h,
                SUM(CASE WHEN '.self::RESPONSE_HOURS.' >= 24 AND '.self::RESPONSE_HOURS.' < 72 THEN 1 ELSE 0 END) as d1_3d,
                SUM(CASE WHEN '.self::RESPONSE_HOURS.' >= 72 THEN 1 ELSE 0 END) as over_3d
            ')
            ->first();

        return [
            '<1h' => (int) ($rows->under_1h ?? 0),
            '1-4h' => (int) ($rows->h1_4h ?? 0),
            '4-24h' => (int) ($rows->h4_24h ?? 0),
            '1-3d' => (int) ($rows->d1_3d ?? 0),
            '>3d' => (int) ($rows->over_3d ?? 0),
        ];
    }

    /**
     * Approximate median by fetching the middle row value.
     *
     * @param  Builder  $query
     */
    private function getMedian($query, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $offset = (int) floor($total / 2);

        $row = (clone $query)
            ->selectRaw(self::RESPONSE_HOURS.' as hours')
            ->orderByRaw(self::RESPONSE_HOURS)
            ->skip($offset)
            ->take(1)
            ->first();

        return round((float) ($row->hours ?? 0), 2);
    }

    /**
     * @param  Builder  $query
     * @return array<int, array{date: string, avg_hours: float}>
     */
    private function getTrend($query): array
    {
        return (clone $query)
            ->selectRaw("DATE_FORMAT(reviews.review_time, '%Y-%m-%d') as date, AVG(".self::RESPONSE_HOURS.') as avg_hours')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'avg_hours' => round((float) $row->avg_hours, 2),
            ])
            ->toArray();
    }
}
