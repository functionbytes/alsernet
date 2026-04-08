<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kpis' => $this->formatKpis(),
            'ratingTrends' => $this->formatRatingTrends(),
            'locationStats' => $this->formatLocationStats(),
            'reviewsByDay' => $this->formatReviewsByDay(),
            'ratingDistribution' => $this->formatRatingDistribution(),
            'recentReviews' => $this->resource['recent_reviews'] ?? [],
            'attentionNeeded' => $this->resource['attention_needed'] ?? [],
            'avgResponseTime' => $this->resource['avg_response_time'] ?? ['hours' => 0, 'formatted' => '0 horas'],
            'topReviewers' => $this->resource['top_reviewers'] ?? [],
            'sentimentTrend' => $this->formatSentimentTrend(),
        ];
    }

    private function formatKpis(): array
    {
        return [
            'total' => $this->resource['kpis']['total'] ?? 0,
            'avgRating' => (float) ($this->resource['kpis']['avg_rating'] ?? 0),
            'unanswered' => $this->resource['kpis']['unanswered'] ?? 0,
            'responseRate' => (float) ($this->resource['kpis']['response_rate'] ?? 0),
        ];
    }

    private function formatRatingTrends(): array
    {
        if (! isset($this->resource['rating_trends'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = [];
        $avgRatings = [];
        $counts = [];

        foreach ($this->resource['rating_trends'] as $trend) {
            $labels[] = $trend['month'];
            $avgRatings[] = (float) $trend['avg_rating'];
            $counts[] = $trend['count'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Calificación promedio',
                    'data' => $avgRatings,
                    'borderColor' => '#90bb13',
                    'backgroundColor' => 'rgba(144, 187, 19, 0.1)',
                    'yAxisID' => 'y-rating',
                ],
                [
                    'label' => 'Cantidad de reseñas',
                    'data' => $counts,
                    'borderColor' => '#13C672',
                    'backgroundColor' => 'rgba(19, 198, 114, 0.1)',
                    'yAxisID' => 'y-count',
                ],
            ],
        ];
    }

    private function formatLocationStats(): array
    {
        if (! isset($this->resource['location_stats'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = [];
        $counts = [];
        $avgRatings = [];

        foreach ($this->resource['location_stats'] as $location) {
            $labels[] = $location['location_name'];
            $counts[] = $location['review_count'];
            $avgRatings[] = (float) $location['avg_rating'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad de reseñas',
                    'data' => $counts,
                    'backgroundColor' => '#90bb13',
                ],
            ],
        ];
    }

    private function formatReviewsByDay(): array
    {
        if (! isset($this->resource['reviews_by_day'])) {
            return ['labels' => [], 'datasets' => [], 'dailyData' => [], 'currTotal' => 0, 'prevTotal' => 0, 'currUnanswered' => 0, 'prevUnanswered' => 0];
        }

        $days = $this->resource['reviews_by_day'];
        $half = (int) ceil(count($days) / 2);
        $labels = [];
        $counts = [];

        foreach ($days as $day) {
            $labels[] = $day['date'];
            $counts[] = $day['count'];
        }

        $currTotal = array_sum(array_slice($counts, -$half));
        $prevTotal = array_sum(array_slice($counts, 0, $half));

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Reseñas recibidas',
                'data' => $counts,
                'borderColor' => '#b10100',
                'backgroundColor' => 'rgba(177,1,0,0.1)',
                'fill' => true,
            ]],
            'dailyData' => array_map(fn ($d) => ['count' => $d['count'], 'unanswered' => 0], $days),
            'currTotal' => $currTotal,
            'prevTotal' => $prevTotal,
            'currUnanswered' => $this->resource['kpis']['unanswered'] ?? 0,
            'prevUnanswered' => 0,
        ];
    }

    private function formatRatingDistribution(): array
    {
        if (! isset($this->resource['rating_distribution'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        return [
            'labels' => ['5 estrellas', '4 estrellas', '3 estrellas', '2 estrellas', '1 estrella'],
            'datasets' => [
                [
                    'label' => 'Distribución de calificaciones',
                    'data' => [
                        $this->resource['rating_distribution']['5'] ?? 0,
                        $this->resource['rating_distribution']['4'] ?? 0,
                        $this->resource['rating_distribution']['3'] ?? 0,
                        $this->resource['rating_distribution']['2'] ?? 0,
                        $this->resource['rating_distribution']['1'] ?? 0,
                    ],
                    'backgroundColor' => [
                        '#13C672',
                        '#90bb13',
                        '#FEC90F',
                        '#FA896B',
                        '#d32f2f',
                    ],
                ],
            ],
        ];
    }

    private function formatSentimentTrend(): array
    {
        if (! isset($this->resource['sentiment_trend'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = [];
        $positive = [];
        $neutral = [];
        $negative = [];

        foreach ($this->resource['sentiment_trend'] as $day) {
            $labels[] = $day['date'];
            $positive[] = $day['positive'];
            $neutral[] = $day['neutral'];
            $negative[] = $day['negative'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Positivas (4-5★)',
                    'data' => $positive,
                    'borderColor' => '#13C672',
                    'backgroundColor' => 'rgba(19, 198, 114, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Neutrales (3★)',
                    'data' => $neutral,
                    'borderColor' => '#FEC90F',
                    'backgroundColor' => 'rgba(254, 201, 15, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Negativas (1-2★)',
                    'data' => $negative,
                    'borderColor' => '#FA896B',
                    'backgroundColor' => 'rgba(250, 137, 107, 0.2)',
                    'fill' => true,
                ],
            ],
        ];
    }
}
