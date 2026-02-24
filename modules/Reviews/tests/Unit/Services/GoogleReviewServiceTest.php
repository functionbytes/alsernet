<?php

namespace Modules\Reviews\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\GoogleReviewService;
use Modules\Reviews\Tests\TestCase;

class GoogleReviewServiceTest extends TestCase
{
    private GoogleReviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoogleReviewService::class);
    }

    public function test_fetch_reviews_calls_google_api(): void
    {
        $connection = $this->createConnection();
        $locationId = 'locations/123';

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(),
                ],
            ], 200),
        ]);

        $reviews = $this->service->fetchReviews($connection, $locationId);

        $this->assertCount(1, $reviews);
        Http::assertSent(function ($request) use ($connection) {
            return str_contains($request->header('Authorization')[0] ?? '', $connection->access_token);
        });
    }

    public function test_sync_reviews_creates_new_reviews(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData([
                        'reviewId' => 'review-123',
                        'starRating' => 'FIVE',
                    ]),
                ],
            ], 200),
        ]);

        Event::fake([ReviewSynced::class]);

        $count = $this->service->syncReviews($location);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reviews', [
            'location_id' => $location->id,
            'google_review_id' => 'review-123',
        ]);

        Event::assertDispatched(ReviewSynced::class);
    }

    public function test_sync_reviews_updates_existing_reviews(): void
    {
        $location = $this->createLocation();
        $existingReview = Review::factory()->for($location)->create([
            'google_review_id' => 'review-123',
            'comment' => 'Old comment',
        ]);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData([
                        'reviewId' => 'review-123',
                        'comment' => 'Updated comment',
                    ]),
                ],
            ], 200),
        ]);

        $this->service->syncReviews($location);

        $this->assertDatabaseHas('reviews', [
            'id' => $existingReview->id,
            'comment' => 'Updated comment',
        ]);
    }

    public function test_sync_reviews_creates_moderation_for_new_reviews(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);
        config(['reviews.general.default_moderation_visible' => true]);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(['reviewId' => 'review-123']),
                ],
            ], 200),
        ]);

        $this->service->syncReviews($location);

        $review = Review::query()->where('google_review_id', 'review-123')->first();

        $this->assertDatabaseHas('review_moderations', [
            'review_id' => $review->id,
            'is_visible' => true,
        ]);
    }

    public function test_sync_reviews_does_not_duplicate_moderation(): void
    {
        $location = $this->createLocation();
        $review = Review::factory()->for($location)->create([
            'google_review_id' => 'review-123',
        ]);

        $this->createModeration($review);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'reviews' => [
                    $this->createGoogleReviewData(['reviewId' => 'review-123']),
                ],
            ], 200),
        ]);

        $this->service->syncReviews($location);

        $this->assertDatabaseCount('review_moderations', 1);
    }

    public function test_sync_reviews_maps_star_ratings_correctly(): void
    {
        $location = $this->createLocation();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        $reviewData = [
            ['reviewId' => 'r1', 'starRating' => 'ONE'],
            ['reviewId' => 'r2', 'starRating' => 'TWO'],
            ['reviewId' => 'r3', 'starRating' => 'THREE'],
            ['reviewId' => 'r4', 'starRating' => 'FOUR'],
            ['reviewId' => 'r5', 'starRating' => 'FIVE'],
        ];

        Http::fake([
            '*' => Http::response([
                'reviews' => array_map(fn ($r) => $this->createGoogleReviewData($r), $reviewData),
            ], 200),
        ]);

        $this->service->syncReviews($location);

        $this->assertDatabaseHas('reviews', ['google_review_id' => 'r1', 'star_rating' => ReviewRating::ONE->value]);
        $this->assertDatabaseHas('reviews', ['google_review_id' => 'r5', 'star_rating' => ReviewRating::FIVE->value]);
    }

    public function test_publish_reply_sends_to_google_api(): void
    {
        $review = $this->createReview();
        $reply = ReviewReply::factory()->for($review)->approved()->create([
            'reply_text' => 'Thank you for your review!',
        ]);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([
                'comment' => 'Thank you for your review!',
                'updateTime' => now()->toIso8601String(),
            ], 200),
        ]);

        $result = $this->service->publishReply($reply);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/reply');
        });

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'google_reply_text' => 'Thank you for your review!',
        ]);
    }

    public function test_publish_reply_throws_exception_on_api_error(): void
    {
        $review = $this->createReview();
        $reply = ReviewReply::factory()->for($review)->approved()->create();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response(['error' => 'Forbidden'], 403),
        ]);

        $this->expectException(\Exception::class);

        $this->service->publishReply($reply);
    }

    public function test_delete_reply_removes_from_google(): void
    {
        $review = $this->createReview();
        $review->update(['google_reply_text' => 'Existing reply']);

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);

        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $result = $this->service->deleteReply($review);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE';
        });

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'google_reply_text' => null,
        ]);
    }
}
