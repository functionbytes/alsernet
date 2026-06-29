<?php

namespace Modules\Reviews\Services\Fetchers;

use Modules\Reviews\Models\ReviewGoogleLocation;

interface ReviewFetchStrategyInterface
{
    public function supports(ReviewGoogleLocation $location): bool;

    /**
     * @return array<int, array{
     *     reviewId: string,
     *     reviewer: array{displayName: string, profilePhotoUrl: string|null},
     *     starRating: string,
     *     comment: string|null,
     *     createTime: string,
     *     updateTime: string|null,
     *     _source: string
     * }>
     */
    public function fetchReviews(ReviewGoogleLocation $location): array;
}
