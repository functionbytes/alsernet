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
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = [];
        $counts = [];

        foreach ($this->resource['reviews_by_day'] as $day) {
            $labels[] = $day['date'];
            $counts[] = $day['count'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Reseñas recibidas',
                    'data' => $counts,
                    'borderColor' => '#90bb13',
                    'backgroundColor' => 'rgba(144, 187, 19, 0.2)',
                    'fill' => true,
                ],
            ],
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
}
