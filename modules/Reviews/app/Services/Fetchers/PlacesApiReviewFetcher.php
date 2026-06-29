<?php

namespace Modules\Reviews\Services\Fetchers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Models\ReviewGoogleLocation;

class PlacesApiReviewFetcher implements ReviewFetchStrategyInterface
{
    public function supports(ReviewGoogleLocation $location): bool
    {
        return $location->place_id !== null
            && $location->sync_strategy?->value === 'places_api'
            && ! empty(config('reviews.google.maps_api_key'));
    }

    public function fetchReviews(ReviewGoogleLocation $location): array
    {
        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $location->place_id,
                'fields' => 'reviews,name,rating,user_ratings_total,place_id',
                'key' => config('reviews.google.maps_api_key'),
                'language' => 'es',
            ]);

            if (! $response->successful()) {
                Log::warning('Places API returned non-200 response', [
                    'location_id' => $location->id,
                    'place_id' => $location->place_id,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                Log::warning('Places API returned non-OK status', [
                    'location_id' => $location->id,
                    'api_status' => $data['status'] ?? 'unknown',
                ]);

                return [];
            }

            return $this->mapReviews($data['result']['reviews'] ?? [], $location->place_id);
        } catch (\Throwable $e) {
            Log::warning('PlacesApiReviewFetcher failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $reviews
     * @return array<int, array<string, mixed>>
     */
    private function mapReviews(array $reviews, string $placeId): array
    {
        return array_map(function (array $review, int $index) use ($placeId): array {
            return [
                'reviewId' => "places_api_{$placeId}_{$index}",
                'reviewer' => [
                    'displayName' => $review['author_name'] ?? 'Anonymous',
                    'profilePhotoUrl' => $review['profile_photo_url'] ?? null,
                ],
                'starRating' => $this->intToStarRating((int) ($review['rating'] ?? 3)),
                'comment' => $review['text'] ?? null,
                'createTime' => Carbon::createFromTimestamp($review['time'] ?? time())->toIso8601String(),
                'updateTime' => null,
                '_source' => ReviewSource::PlacesApi->value,
            ];
        }, $reviews, array_keys($reviews));
    }

    private function intToStarRating(int $rating): string
    {
        return match ($rating) {
            1 => 'ONE',
            2 => 'TWO',
            3 => 'THREE',
            4 => 'FOUR',
            5 => 'FIVE',
            default => 'THREE',
        };
    }
}
