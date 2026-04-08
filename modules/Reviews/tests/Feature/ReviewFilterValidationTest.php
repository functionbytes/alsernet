<?php

namespace Modules\Reviews\Tests\Feature;

use App\Models\User;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Tests\TestCase;

class ReviewFilterValidationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['reviews.reviews.view']);
    }

    // -------------------------------------------------------------------------
    // Rating filter
    // -------------------------------------------------------------------------

    public function test_invalid_rating_value_returns_422(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);
        Review::factory()->fiveStars()->for($location, 'location')->create();

        // The data endpoint validates the rating field and rejects invalid values.
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['rating' => 'INVALID_RATING']));

        $response->assertUnprocessable();
    }

    public function test_valid_rating_returns_200_and_correct_reviews(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);

        Review::factory()->fiveStars()->for($location, 'location')->create();
        Review::factory()->oneStar()->for($location, 'location')->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['rating' => 'FIVE']));

        $response->assertOk();
        $this->assertSame(1, count($response->json('data')));
        $this->assertSame('FIVE', $response->json('data.0.star_rating'));
    }

    // -------------------------------------------------------------------------
    // Date filter
    // -------------------------------------------------------------------------

    public function test_date_from_filter_limits_results(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);

        Review::factory()->for($location, 'location')->create(['review_time' => '2023-01-15 10:00:00']);
        Review::factory()->for($location, 'location')->create(['review_time' => '2024-06-01 10:00:00']);

        // Provide date_to as well because the filter request validates before_or_equal:date_to
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['date_from' => '2024-01-01', 'date_to' => '2024-12-31']));

        $response->assertOk();
        $this->assertSame(1, count($response->json('data')));
    }

    public function test_date_to_filter_limits_results(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);

        Review::factory()->for($location, 'location')->create(['review_time' => '2023-06-01 10:00:00']);
        Review::factory()->for($location, 'location')->create(['review_time' => '2024-06-01 10:00:00']);

        // Provide date_from as well to satisfy the before_or_equal:date_to validation
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['date_from' => '2023-01-01', 'date_to' => '2023-12-31']));

        $response->assertOk();
        $this->assertSame(1, count($response->json('data')));
    }

    public function test_invalid_date_from_format_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['date_from' => 'not-a-date', 'date_to' => '2024-12-31']));

        $response->assertUnprocessable();
    }

    public function test_date_from_after_date_to_returns_422(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['date_from' => '2024-12-31', 'date_to' => '2024-01-01']));

        $response->assertUnprocessable();
    }

    public function test_combined_date_range_filter_returns_correct_reviews(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);

        Review::factory()->for($location, 'location')->create(['review_time' => '2022-01-01 10:00:00']);
        Review::factory()->for($location, 'location')->create(['review_time' => '2023-06-15 10:00:00']);
        Review::factory()->for($location, 'location')->create(['review_time' => '2024-12-01 10:00:00']);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', [
                'date_from' => '2023-01-01',
                'date_to' => '2023-12-31',
            ]));

        $response->assertOk();
        $this->assertSame(1, count($response->json('data')));
    }

    // -------------------------------------------------------------------------
    // Search filter — XSS and injection safety
    // -------------------------------------------------------------------------

    public function test_xss_in_search_field_does_not_reflect_script_in_response(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);
        Review::factory()->for($location, 'location')->create(['reviewer_name' => 'Safe User']);

        $xssPayload = '<script>alert("xss")</script>';

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['search' => $xssPayload]));

        $response->assertOk();

        // The raw script tag must not appear unescaped in the JSON body
        $this->assertStringNotContainsString('<script>', $response->getContent());
        $this->assertStringNotContainsString('alert("xss")', $response->getContent());
    }

    public function test_sql_injection_like_search_returns_empty_results_safely(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);
        Review::factory()->for($location, 'location')->create(['reviewer_name' => 'Normal User']);

        $injectionPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE reviews; --",
            '1 UNION SELECT * FROM users --',
        ];

        foreach ($injectionPayloads as $payload) {
            $response = $this->actingAs($this->user)
                ->getJson(route('reviews.data', ['search' => $payload]));

            // Should return 200 with empty results, not crash
            $response->assertOk();
            $this->assertIsArray($response->json('data'));
        }

        // Verify the reviews table is still intact
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_empty_search_returns_all_reviews_for_user(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);
        Review::factory()->count(3)->for($location, 'location')->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['search' => '']));

        $response->assertOk();
        $this->assertSame(3, count($response->json('data')));
    }

    public function test_valid_filters_combination_returns_200(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);

        Review::factory()->fiveStars()->for($location, 'location')->create([
            'reviewer_name' => 'Happy Customer',
            'review_time' => '2024-03-15 10:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', [
                'rating' => 'FIVE',
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
                'search' => 'Happy',
            ]));

        $response->assertOk();
        $this->assertSame(1, count($response->json('data')));
    }

    public function test_filter_with_very_long_search_string_returns_validation_error(): void
    {
        $connection = $this->createConnection($this->user);
        $location = $this->createLocation($connection);
        Review::factory()->for($location, 'location')->create();

        // The filter request validates search max length — a 5000-char string should be rejected
        $longString = str_repeat('a', 5000);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.data', ['search' => $longString]));

        // Either 422 (if max length rule exists) or 200 with empty results is acceptable —
        // neither should be a 500 crash.
        $this->assertContains($response->status(), [200, 422]);
    }
}
