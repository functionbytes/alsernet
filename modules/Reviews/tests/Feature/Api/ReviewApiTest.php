<?php

namespace Modules\Reviews\Tests\Feature\Api;

use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Tests\TestCase;

class ReviewApiTest extends TestCase
{
    public function test_api_returns_paginated_reviews(): void
    {
        $user = $this->createUser(['reviews.view-api']);
        Review::factory()->count(25)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'reviewer_name',
                        'star_rating',
                        'comment',
                        'review_time',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_api_filters_by_location(): void
    {
        $user = $this->createUser(['reviews.view-api']);
        $location1 = $this->createLocation();
        $location2 = $this->createLocation();

        Review::factory()->for($location1)->count(5)->create();
        Review::factory()->for($location2)->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index', ['location_id' => $location1->id]));

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_api_filters_by_rating(): void
    {
        $user = $this->createUser(['reviews.view-api']);

        Review::factory()->count(3)->create(['star_rating' => ReviewRating::FIVE]);
        Review::factory()->count(2)->create(['star_rating' => ReviewRating::THREE]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index', ['rating' => 'FIVE']));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_filters_by_visibility(): void
    {
        $user = $this->createUser(['reviews.view-api']);

        $visibleReview = $this->createReview();
        $this->createModeration($visibleReview, ['is_visible' => true]);

        $hiddenReview = $this->createReview();
        $this->createModeration($hiddenReview, ['is_visible' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index', ['is_visible' => true]));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson(route('api.v1.reviews.index'));

        $response->assertUnauthorized();
    }

    public function test_api_respects_rate_limit(): void
    {
        $user = $this->createUser(['reviews.view-api']);

        for ($i = 0; $i < 65; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->getJson(route('api.v1.reviews.index'));
        }

        $response->assertStatus(429);
    }

    public function test_api_returns_stats(): void
    {
        $user = $this->createUser(['reviews.view-api']);

        Review::factory()->count(10)->create(['star_rating' => ReviewRating::FIVE]);
        Review::factory()->count(5)->create(['star_rating' => ReviewRating::FOUR]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.stats'));

        $response->assertOk()
            ->assertJsonStructure([
                'total',
                'average_rating',
                'by_rating',
            ]);

        $this->assertSame(15, $response->json('total'));
    }

    public function test_api_eager_loads_relations(): void
    {
        $user = $this->createUser(['reviews.view-api']);
        $review = $this->createReview();
        $this->createModeration($review);
        $this->createReply($review);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index', ['include' => 'location,moderation,replies']));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'location',
                        'moderation',
                        'replies',
                    ],
                ],
            ]);
    }

    public function test_api_search_filters_by_keyword(): void
    {
        $user = $this->createUser(['reviews.view-api']);

        Review::factory()->create(['comment' => 'Excellent service!']);
        Review::factory()->create(['comment' => 'Terrible experience']);
        Review::factory()->create(['reviewer_name' => 'Excellent Customer']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('api.v1.reviews.index', ['search' => 'Excellent']));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
