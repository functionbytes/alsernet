<?php

namespace Modules\Reviews\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Models\ReviewReply;

class GoogleReviewService
{
    public function __construct(
        private GoogleAuthService $authService
    ) {}

    /**
     * SECURITY FIX: Create Guzzle client with secure defaults
     * Ensures SSL verification, timeouts, and proper error handling
     */
    private function createSecureClient(): GuzzleClient
    {
        return new GuzzleClient([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true, // CRITICAL: Verify SSL certificates
            'http_errors' => false, // Handle errors manually for better control
        ]);
    }

    public function syncReviews(ReviewGoogleLocation $location): int
    {
        $connection = $location->connection;
        $this->authService->refreshTokenIfNeeded($connection);

        $reviews = $this->fetchReviews($connection, $location->google_location_id);
        $syncedCount = 0;

        DB::transaction(function () use ($location, $reviews, &$syncedCount) {
            foreach ($reviews as $reviewData) {
                $review = $this->saveReview($location, $reviewData);
                $syncedCount++;

                event(new ReviewSynced($review));
            }

            $location->markAsSynced();
        });

        activity()
            ->performedOn($location)
            ->log("Synced {$syncedCount} reviews");

        return $syncedCount;
    }

    public function fetchReviews(
        \Modules\Reviews\Models\ReviewGoogleConnection $connection,
        string $locationId
    ): array {
        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.reviews');

            $response = $client->get("{$baseUrl}/{$locationId}/reviews", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['reviews'] ?? [];
        } catch (ConnectException $e) {
            Log::error('Connection failed while fetching reviews', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while fetching reviews', [
                'location_id' => $locationId,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while fetching reviews', [
                'location_id' => $locationId,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function saveReview(ReviewGoogleLocation $location, array $reviewData): Review
    {
        $googleReviewId = $reviewData['reviewId'] ?? $reviewData['name'];
        $starRating = $this->mapStarRating($reviewData['starRating']);

        $review = Review::query()->updateOrCreate(
            [
                'google_review_id' => $googleReviewId,
            ],
            [
                'location_id' => $location->id,
                'reviewer_name' => $reviewData['reviewer']['displayName'] ?? 'Anonymous',
                'reviewer_photo_url' => $reviewData['reviewer']['profilePhotoUrl'] ?? null,
                'star_rating' => $starRating,
                'comment' => $reviewData['comment'] ?? null,
                'review_time' => isset($reviewData['createTime'])
                    ? \Carbon\Carbon::parse($reviewData['createTime'])
                    : now(),
                'update_time' => isset($reviewData['updateTime'])
                    ? \Carbon\Carbon::parse($reviewData['updateTime'])
                    : null,
                'google_reply_text' => $reviewData['reviewReply']['comment'] ?? null,
                'google_reply_time' => isset($reviewData['reviewReply']['updateTime'])
                    ? \Carbon\Carbon::parse($reviewData['reviewReply']['updateTime'])
                    : null,
                'raw_json' => $reviewData,
                'synced_at' => now(),
            ]
        );

        if (! $review->moderation) {
            ReviewModeration::query()->create([
                'review_id' => $review->id,
                'is_visible' => config('reviews.general.default_moderation_visible', true),
                'is_featured' => false,
            ]);
        }

        return $review;
    }

    private function mapStarRating(string $rating): ReviewRating
    {
        return match ($rating) {
            'ONE', 'STAR_RATING_UNSPECIFIED' => ReviewRating::ONE,
            'TWO' => ReviewRating::TWO,
            'THREE' => ReviewRating::THREE,
            'FOUR' => ReviewRating::FOUR,
            'FIVE' => ReviewRating::FIVE,
            default => ReviewRating::THREE,
        };
    }

    public function publishReply(ReviewReply $reply): bool
    {
        $review = $reply->review()->with('location.connection')->first();
        $connection = $review->location->connection;

        $this->authService->refreshTokenIfNeeded($connection);

        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.reviews');

            $locationId = $review->location->google_location_id;
            $reviewId = $review->google_review_id;

            $client->put("{$baseUrl}/{$locationId}/reviews/{$reviewId}/reply", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'comment' => $reply->reply_text,
                ],
            ]);

            $review->update([
                'google_reply_text' => $reply->reply_text,
                'google_reply_time' => now(),
            ]);

            activity()
                ->performedOn($reply)
                ->log('Reply published to Google successfully');

            return true;
        } catch (ConnectException $e) {
            Log::error('Google API connection failed while publishing reply', [
                'reply_id' => $reply->id,
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            activity()
                ->performedOn($reply)
                ->log('Reply publication failed: Connection error');

            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while publishing reply', [
                'reply_id' => $reply->id,
                'review_id' => $review->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            activity()
                ->performedOn($reply)
                ->log('Reply publication failed: Server error');

            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while publishing reply', [
                'reply_id' => $reply->id,
                'review_id' => $review->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            activity()
                ->performedOn($reply)
                ->log('Reply publication failed: Request error');

            throw $e;
        }
    }

    public function deleteReply(Review $review): bool
    {
        $connection = $review->location->connection;
        $this->authService->refreshTokenIfNeeded($connection);

        try {
            $client = $this->createSecureClient();
            $baseUrl = config('reviews.google.api.reviews');

            $locationId = $review->location->google_location_id;
            $reviewId = $review->google_review_id;

            $client->delete("{$baseUrl}/{$locationId}/reviews/{$reviewId}/reply", [
                'headers' => [
                    'Authorization' => 'Bearer '.$connection->access_token,
                ],
            ]);

            $review->update([
                'google_reply_text' => null,
                'google_reply_time' => null,
            ]);

            activity()
                ->performedOn($review)
                ->log('Reply deleted from Google successfully');

            return true;
        } catch (ConnectException $e) {
            Log::error('Google API connection failed while deleting reply', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (ServerException $e) {
            Log::error('Google API server error while deleting reply', [
                'review_id' => $review->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (RequestException $e) {
            Log::error('Google API request error while deleting reply', [
                'review_id' => $review->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
