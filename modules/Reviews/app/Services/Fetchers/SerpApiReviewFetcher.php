<?php

namespace Modules\Reviews\Services\Fetchers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Models\ReviewGoogleLocation;

class SerpApiReviewFetcher implements ReviewFetchStrategyInterface
{
    public function supports(ReviewGoogleLocation $location): bool
    {
        return $location->place_id !== null
            && $location->sync_strategy?->value === 'serpapi'
            && ! empty(config('reviews.google.serpapi_key'));
    }

    public function fetchReviews(ReviewGoogleLocation $location): array
    {
        try {
            $response = Http::timeout(10)->get('https://serpapi.com/search', [
                'engine' => 'google_maps_reviews',
                'place_id' => $location->place_id,
                'api_key' => config('reviews.google.serpapi_key'),
                'hl' => 'es',
                'sort_by' => 'newestFirst',
            ]);

            if (! $response->successful()) {
                Log::warning('SerpApi returned non-200 response', [
                    'location_id' => $location->id,
                    'place_id' => $location->place_id,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            return $this->mapReviews($data['reviews'] ?? []);
        } catch (\Throwable $e) {
            Log::warning('SerpApiReviewFetcher failed', [
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
    private function mapReviews(array $reviews): array
    {
        return array_map(function (array $review): array {
            return [
                'reviewId' => $review['review_id'] ?? 'serpapi_'.uniqid(),
                'reviewer' => [
                    'displayName' => $review['user']['name'] ?? 'Anonymous',
                    'profilePhotoUrl' => $review['user']['thumbnail'] ?? null,
                ],
                'starRating' => $this->intToStarRating((int) ($review['rating'] ?? 3)),
                'comment' => $review['snippet'] ?? null,
                'createTime' => $review['iso_date'] ?? now()->toIso8601String(),
                'updateTime' => null,
                '_source' => ReviewSource::SerpApi->value,
            ];
        }, $reviews);
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
