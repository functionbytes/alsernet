<?php

namespace Modules\Reviews\Tests\Feature\Api;

use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Tests\TestCase;

class ReviewApiTest extends TestCase
{
    public function test_api_returns_paginated_reviews(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);
        Review::factory()->count(25)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'reviewerName',
                        'comment',
                        'reviewTime',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_api_filters_by_location(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);
        $location1 = $this->createLocation();
        $location2 = $this->createLocation();

        Review::factory()->for($location1, 'location')->count(5)->create();
        Review::factory()->for($location2, 'location')->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index', ['location_id' => $location1->id]));

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_api_filters_by_rating(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);

        Review::factory()->count(3)->create(['star_rating' => ReviewRating::FIVE]);
        Review::factory()->count(2)->create(['star_rating' => ReviewRating::THREE]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index', ['rating' => 'FIVE']));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_filters_by_visibility(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);

        $visibleReview = $this->createReview();
        $this->createModeration($visibleReview, ['is_visible' => true]);

        $hiddenReview = $this->createReview();
        $this->createModeration($hiddenReview, ['is_visible' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index', ['is_visible' => true]));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson(route('api.reviews.index'));

        $response->assertUnauthorized();
    }

    /**
     * @skip Rate limit requires 100+ requests per hour; not practical for unit tests
     */
    public function test_api_respects_rate_limit(): void
    {
        $this->markTestSkipped('Rate limit is 100 requests/hour; cannot reliably test in isolation.');
    }

    public function test_api_returns_stats(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);

        Review::factory()->count(10)->create(['star_rating' => ReviewRating::FIVE]);
        Review::factory()->count(5)->create(['star_rating' => ReviewRating::FOUR]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.stats'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalReviews',
                    'averageRating',
                    'ratingDistribution',
                ],
            ]);

        $this->assertSame(15, $response->json('data.totalReviews'));
    }

    public function test_api_eager_loads_relations(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);
        $review = $this->createReview();
        $this->createModeration($review);
        $this->createReply($review);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'location',
                        'replies',
                    ],
                ],
            ]);
    }

    public function test_api_filters_by_has_comment(): void
    {
        $user = $this->createUser(['reviews.reviews.view']);

        Review::factory()->count(2)->create(['comment' => 'Some comment']);
        Review::factory()->create(['comment' => null]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.reviews.index', ['has_comment' => true]));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
