<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\GoogleReviewService;
use Modules\Reviews\Tests\TestCase;

class ReviewSyncTest extends TestCase
{
    private GoogleReviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoogleReviewService::class);
    }

    public function test_sync_job_creates_new_reviews(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(['reviewId' => 'new-review-123']),
                ],
            ], 200),
        ]);

        Event::fake([ReviewSynced::class]);

        SyncGoogleReviewsJob::dispatchSync($location);

        $this->assertDatabaseHas('reviews', [
            'location_id' => $location->id,
            'google_review_id' => 'new-review-123',
        ]);

        Event::assertDispatched(ReviewSynced::class);
    }

    public function test_sync_job_updates_existing_reviews(): void
    {
        $location = $this->createLocation();
        $existingReview = Review::factory()->for($location, 'location')->create([
            'google_review_id' => 'review-456',
            'comment' => 'Old comment',
        ]);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData([
                        'reviewId' => 'review-456',
                        'comment' => 'Updated comment from Google',
                    ]),
                ],
            ], 200),
        ]);

        SyncGoogleReviewsJob::dispatchSync($location);

        $this->assertDatabaseHas('reviews', [
            'id' => $existingReview->id,
            'comment' => 'Updated comment from Google',
        ]);
    }

    public function test_sync_job_creates_moderation_record(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);
        config(['reviews.general.default_moderation_visible' => false]);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(['reviewId' => 'review-789']),
                ],
            ], 200),
        ]);

        SyncGoogleReviewsJob::dispatchSync($location);

        $review = Review::query()->where('google_review_id', 'review-789')->first();

        $this->assertDatabaseHas('review_moderations', [
            'review_id' => $review->id,
            'is_visible' => false,
        ]);
    }

    public function test_sync_job_handles_expired_token(): void
    {
        $location = $this->createLocation();
        $location->connection->update([
            'token_expires_at' => now()->subHour(),
            'refresh_token' => 'refresh-token',
        ]);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed-token',
                'expires_in' => 3600,
            ], 200),
            '*' => Http::response([
                'reviews' => [],
            ], 200),
        ]);

        SyncGoogleReviewsJob::dispatchSync($location);

        $this->assertSame('refreshed-token', $location->connection->fresh()->access_token);
    }

    public function test_sync_skips_duplicate_reviews(): void
    {
        $location = $this->createLocation();
        Review::factory()->for($location, 'location')->create([
            'google_review_id' => 'duplicate-review',
        ]);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(['reviewId' => 'duplicate-review']),
                ],
            ], 200),
        ]);

        SyncGoogleReviewsJob::dispatchSync($location);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_sync_dispatches_review_synced_event(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(),
                ],
            ], 200),
        ]);

        Event::fake([ReviewSynced::class]);

        SyncGoogleReviewsJob::dispatchSync($location);

        Event::assertDispatched(ReviewSynced::class);
    }
}
