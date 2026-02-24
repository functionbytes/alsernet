<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalReviews' => $this->resource['total'],
            'averageRating' => (float) number_format($this->resource['average_rating'] ?? 0, 2),
            'ratingDistribution' => [
                '5' => $this->resource['rating_distribution']['5'] ?? 0,
                '4' => $this->resource['rating_distribution']['4'] ?? 0,
                '3' => $this->resource['rating_distribution']['3'] ?? 0,
                '2' => $this->resource['rating_distribution']['2'] ?? 0,
                '1' => $this->resource['rating_distribution']['1'] ?? 0,
            ],
            'totalVisible' => $this->resource['total_visible'] ?? 0,
            'totalFeatured' => $this->resource['total_featured'] ?? 0,
            'totalWithComment' => $this->resource['with_comment'] ?? 0,
            'totalWithGoogleReply' => $this->resource['with_reply'] ?? 0,
        ];
    }
}
