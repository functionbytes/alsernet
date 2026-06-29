<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\Fetchers\ReviewFetchStrategyInterface;

class ReviewFetchOrchestrator
{
    /**
     * @param  array<int, ReviewFetchStrategyInterface>  $fetchers
     */
    public function __construct(
        private readonly array $fetchers
    ) {}

    public function fetchReviews(ReviewGoogleLocation $location): array
    {
        foreach ($this->fetchers as $fetcher) {
            if (! $fetcher->supports($location)) {
                continue;
            }

            $fetcherClass = class_basename($fetcher);

            Log::info("Using {$fetcherClass} for location {$location->id}", [
                'location_id' => $location->id,
                'sync_strategy' => $location->sync_strategy?->value,
            ]);

            return $fetcher->fetchReviews($location);
        }

        Log::warning('No review fetcher supports this location', [
            'location_id' => $location->id,
            'sync_strategy' => $location->sync_strategy?->value,
            'place_id' => $location->place_id,
        ]);

        return [];
    }
}
