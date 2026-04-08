<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewCompetitor;
use Modules\Reviews\Models\ReviewCompetitorSnapshot;
use Modules\Reviews\Models\ReviewGoogleLocation;

class CompetitorTrackingService
{
    /**
     * Fetch rating and review count for a competitor via Google Places Details API.
     * Returns null when the API key is missing, place_id is unset, or the request fails.
     *
     * @return array{avg_rating: float, review_count: int}|null
     */
    public function fetchCompetitorData(ReviewCompetitor $competitor): ?array
    {
        $apiKey = config('reviews.google.maps_api_key');

        if (! $apiKey || ! $competitor->place_id) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $competitor->place_id,
                'fields' => 'rating,user_ratings_total',
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json('result');

            if (empty($result)) {
                return null;
            }

            return [
                'avg_rating' => (float) ($result['rating'] ?? 0),
                'review_count' => (int) ($result['user_ratings_total'] ?? 0),
            ];
        } catch (\Throwable $e) {
            Log::warning('CompetitorTrackingService: failed to fetch data', [
                'competitor_id' => $competitor->id,
                'place_id' => $competitor->place_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Snapshot all competitors for a location and return the count of successes.
     */
    public function snapshotAll(int $locationId): int
    {
        $competitors = ReviewCompetitor::query()
            ->where('location_id', $locationId)
            ->get();

        $saved = 0;

        foreach ($competitors as $competitor) {
            $data = $this->fetchCompetitorData($competitor);

            if ($data === null) {
                continue;
            }

            ReviewCompetitorSnapshot::query()->create([
                'competitor_id' => $competitor->id,
                'avg_rating' => $data['avg_rating'],
                'review_count' => $data['review_count'],
                'captured_at' => now(),
            ]);

            $competitor->update(['last_checked_at' => now()]);

            $saved++;
        }

        return $saved;
    }

    /**
     * Return our location stats versus each competitor's latest snapshot.
     *
     * @return array{us: array{name: string, avg_rating: float, total_reviews: int}, competitors: list<array{id: int, name: string, avg_rating: float|null, review_count: int|null, last_checked_at: string|null}>}
     */
    public function getComparisonData(int $locationId): array
    {
        $location = ReviewGoogleLocation::query()->findOrFail($locationId);

        $us = [
            'name' => $location->name,
            'avg_rating' => (float) $location->average_rating,
            'total_reviews' => (int) $location->total_reviews,
        ];

        $competitors = ReviewCompetitor::query()
            ->with('latestSnapshot')
            ->where('location_id', $locationId)
            ->get()
            ->map(fn (ReviewCompetitor $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'avg_rating' => $c->latestSnapshot ? (float) $c->latestSnapshot->avg_rating : null,
                'review_count' => $c->latestSnapshot ? (int) $c->latestSnapshot->review_count : null,
                'last_checked_at' => $c->last_checked_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();

        return ['us' => $us, 'competitors' => $competitors];
    }
}
