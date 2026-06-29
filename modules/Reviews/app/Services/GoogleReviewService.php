<?php

namespace Modules\Reviews\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Enums\ReviewSource;
use Modules\Reviews\Enums\SyncStrategy;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Exceptions\CircuitBreakerOpenException;
use Modules\Reviews\Exceptions\RateLimitExceededException;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Models\ReviewReply;

class GoogleReviewService
{
    public function __construct(
        private readonly GoogleApiClient $apiClient,
        private readonly ?ReviewFetchOrchestrator $orchestrator = null
    ) {}

    public function syncReviews(ReviewGoogleLocation $location): int
    {
        $location->loadMissing('connection.user');

        $reviews = $location->sync_strategy !== SyncStrategy::Oauth && $this->orchestrator
            ? $this->orchestrator->fetchReviews($location)
            : $this->fetchReviews($location->connection, $location->google_location_id);
        $syncedCount = 0;

        DB::transaction(function () use ($location, $reviews, &$syncedCount) {
            foreach ($reviews as $reviewData) {
                $review = $this->saveReview($location, $reviewData);
                $review->setRelation('location', $location);
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
        ReviewGoogleConnection $connection,
        string $locationId
    ): array {
        $baseUrl = config('reviews.google.api.reviews');
        $endpoint = "{$baseUrl}/{$locationId}/reviews";

        try {
            $data = $this->apiClient->get($connection, $endpoint);
        } catch (CircuitBreakerOpenException $e) {
            Log::warning('Google API circuit breaker open — skipping review fetch', [
                'connection_id' => $connection->id,
                'service_key' => $e->serviceKey,
            ]);

            $connection->update(['status' => ConnectionStatus::ERROR, 'last_error' => 'Service temporarily unavailable (circuit breaker open)']);

            return [];
        } catch (RateLimitExceededException $e) {
            Log::warning('Google API rate limit exceeded — skipping review fetch', [
                'connection_id' => $e->connectionId,
                'limit_type' => $e->limitType,
                'retry_after' => $e->retryAfterSeconds,
            ]);

            return [];
        }

        return $data['reviews'] ?? [];
    }

    private function saveReview(ReviewGoogleLocation $location, array $reviewData): Review
    {
        $googleReviewId = $reviewData['reviewId'] ?? $reviewData['name'];
        $starRating = $this->mapStarRating($reviewData['starRating']);
        $source = $reviewData['_source'] ?? ReviewSource::BusinessProfile->value;

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
                    ? Carbon::parse($reviewData['createTime'])
                    : now(),
                'update_time' => isset($reviewData['updateTime'])
                    ? Carbon::parse($reviewData['updateTime'])
                    : null,
                'google_reply_text' => $reviewData['reviewReply']['comment'] ?? null,
                'google_reply_time' => isset($reviewData['reviewReply']['updateTime'])
                    ? Carbon::parse($reviewData['reviewReply']['updateTime'])
                    : null,
                'source' => $source,
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

        if (! $review?->location?->connection) {
            throw new \RuntimeException('No se puede publicar la respuesta: la ubicación o conexión no existe.');
        }

        $connection = $review->location->connection;

        $baseUrl = config('reviews.google.api.reviews');
        $locationId = $review->location->google_location_id;
        $reviewId = $review->google_review_id;
        $endpoint = "{$baseUrl}/{$locationId}/reviews/{$reviewId}/reply";

        try {
            $this->apiClient->put($connection, $endpoint, [
                'comment' => $reply->reply_text,
            ]);

            $review->update([
                'google_reply_text' => $reply->reply_text,
                'google_reply_time' => now(),
            ]);

            activity()
                ->performedOn($reply)
                ->log('Reply published to Google successfully');

            return true;
        } catch (CircuitBreakerOpenException $e) {
            Log::warning('Google API circuit breaker open — reply not published', [
                'connection_id' => $connection->id,
                'reply_id' => $reply->id,
                'service_key' => $e->serviceKey,
            ]);

            $connection->update(['status' => ConnectionStatus::ERROR, 'last_error' => 'Service temporarily unavailable (circuit breaker open)']);

            activity()
                ->performedOn($reply)
                ->log('Reply publication skipped: circuit breaker open');

            throw $e;
        } catch (RateLimitExceededException $e) {
            Log::warning('Google API rate limit exceeded — reply not published', [
                'connection_id' => $e->connectionId,
                'reply_id' => $reply->id,
                'limit_type' => $e->limitType,
                'retry_after' => $e->retryAfterSeconds,
            ]);

            activity()
                ->performedOn($reply)
                ->log("Reply publication deferred: rate limit ({$e->limitType}), retry after {$e->retryAfterSeconds}s");

            throw $e;
        } catch (\Exception $e) {
            activity()
                ->performedOn($reply)
                ->log('Reply publication failed: '.$e->getMessage());

            throw $e;
        }
    }

    public function reportReview(Review $review, string $reason): bool
    {
        $review->loadMissing('location.connection');

        if (! $review->location?->connection) {
            throw new \RuntimeException('No se puede reportar la reseña: la ubicación o conexión no existe.');
        }

        $connection = $review->location->connection;

        $baseUrl = config('reviews.google.api.reviews');
        $locationId = $review->location->google_location_id;
        $reviewId = $review->google_review_id;
        $endpoint = "{$baseUrl}/{$locationId}/reviews/{$reviewId}/flag";

        try {
            $this->apiClient->post($connection, $endpoint, [
                'flagReason' => $reason,
            ]);

            activity()
                ->performedOn($review)
                ->withProperties(['reason' => $reason])
                ->log('Review reported to Google as inappropriate');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to report review to Google', [
                'review_id' => $review->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteReply(Review $review): bool
    {
        $review->loadMissing('location.connection');

        if (! $review->location?->connection) {
            throw new \RuntimeException('No se puede eliminar la respuesta: la ubicación o conexión no existe.');
        }

        $connection = $review->location->connection;

        $baseUrl = config('reviews.google.api.reviews');
        $locationId = $review->location->google_location_id;
        $reviewId = $review->google_review_id;
        $endpoint = "{$baseUrl}/{$locationId}/reviews/{$reviewId}/reply";

        try {
            $this->apiClient->delete($connection, $endpoint);

            $review->update([
                'google_reply_text' => null,
                'google_reply_time' => null,
            ]);

            activity()
                ->performedOn($review)
                ->log('Reply deleted from Google successfully');

            return true;
        } catch (CircuitBreakerOpenException $e) {
            Log::warning('Google API circuit breaker open — reply not deleted', [
                'connection_id' => $connection->id,
                'review_id' => $review->id,
                'service_key' => $e->serviceKey,
            ]);

            $connection->update(['status' => ConnectionStatus::ERROR, 'last_error' => 'Service temporarily unavailable (circuit breaker open)']);

            activity()
                ->performedOn($review)
                ->log('Reply deletion skipped: circuit breaker open');

            throw $e;
        } catch (RateLimitExceededException $e) {
            Log::warning('Google API rate limit exceeded — reply not deleted', [
                'connection_id' => $e->connectionId,
                'review_id' => $review->id,
                'limit_type' => $e->limitType,
                'retry_after' => $e->retryAfterSeconds,
            ]);

            activity()
                ->performedOn($review)
                ->log("Reply deletion deferred: rate limit ({$e->limitType}), retry after {$e->retryAfterSeconds}s");

            throw $e;
        } catch (\Exception $e) {
            activity()
                ->performedOn($review)
                ->log('Reply deletion failed: '.$e->getMessage());

            throw $e;
        }
    }
}
