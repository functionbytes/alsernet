<?php

namespace Modules\Reviews\Tests\Feature;

use App\Models\User;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Tests\TestCase;

class ReviewAnalyticsDashboardTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['reviews.view']);
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reviews.dashboard'));

        $response->assertOk();
        $response->assertViewIs('reviews::dashboard.index');
    }

    public function test_dashboard_data_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();
        $response->assertJsonStructure([
            'kpis' => [
                'total',
                'avgRating',
                'unanswered',
                'responseRate',
            ],
            'ratingTrends' => [
                'labels',
                'datasets',
            ],
            'ratingDistribution' => [
                'labels',
                'datasets',
            ],
            'locationStats' => [
                'labels',
                'datasets',
            ],
            'reviewsByDay' => [
                'labels',
                'datasets',
            ],
            'sentimentTrend' => [
                'labels',
                'datasets',
            ],
            'recentReviews',
            'attentionNeeded',
            'avgResponseTime' => [
                'hours',
                'formatted',
            ],
            'topReviewers',
        ]);
    }

    public function test_kpis_calculate_correctly_with_reviews(): void
    {
        $location = ReviewGoogleLocation::factory()->create();

        // Create 5 reviews: 3 with 5 stars, 2 with 3 stars
        Review::factory()->count(3)->create([
            'location_id' => $location->id,
            'star_rating' => 'FIVE',
            'comment' => 'Great service!',
            'google_reply_text' => 'Thank you!',
        ]);

        Review::factory()->count(2)->create([
            'location_id' => $location->id,
            'star_rating' => 'THREE',
            'comment' => 'Average experience',
            'google_reply_text' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $kpis = $response->json('kpis');

        $this->assertEquals(5, $kpis['total']);
        $this->assertEquals(4.2, $kpis['avgRating']); // (5+5+5+3+3)/5 = 4.2
        $this->assertEquals(2, $kpis['unanswered']); // 2 without replies
        $this->assertEquals(60.0, $kpis['responseRate']); // 3/5 = 60%
    }

    public function test_recent_reviews_returns_latest_reviews(): void
    {
        $location = ReviewGoogleLocation::factory()->create();

        // Create 15 reviews with different timestamps
        Review::factory()->count(15)->create([
            'location_id' => $location->id,
            'review_time' => now()->subDays(rand(1, 30)),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $recentReviews = $response->json('recentReviews');

        // Should return max 10 reviews
        $this->assertCount(10, $recentReviews);

        // Each review should have required fields
        $this->assertArrayHasKey('id', $recentReviews[0]);
        $this->assertArrayHasKey('reviewer_name', $recentReviews[0]);
        $this->assertArrayHasKey('location_name', $recentReviews[0]);
        $this->assertArrayHasKey('star_rating', $recentReviews[0]);
        $this->assertArrayHasKey('has_reply', $recentReviews[0]);
    }

    public function test_attention_needed_filters_low_ratings_without_replies(): void
    {
        $location = ReviewGoogleLocation::factory()->create();

        // Create low-rated reviews without replies
        Review::factory()->count(3)->create([
            'location_id' => $location->id,
            'star_rating' => 'TWO',
            'comment' => 'Poor service',
            'google_reply_text' => null,
        ]);

        // Create high-rated reviews (should not appear)
        Review::factory()->count(2)->create([
            'location_id' => $location->id,
            'star_rating' => 'FIVE',
            'comment' => 'Excellent!',
            'google_reply_text' => null,
        ]);

        // Create low-rated but replied (should not appear)
        Review::factory()->create([
            'location_id' => $location->id,
            'star_rating' => 'ONE',
            'comment' => 'Terrible',
            'google_reply_text' => 'We apologize',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $attentionNeeded = $response->json('attentionNeeded');

        // Should return only the 3 low-rated unanswered reviews
        $this->assertCount(3, $attentionNeeded);

        foreach ($attentionNeeded as $review) {
            $this->assertArrayHasKey('priority', $review);
            $this->assertEquals('high', $review['priority']); // 2-star = high priority
        }
    }

    public function test_rating_distribution_groups_by_stars(): void
    {
        $location = ReviewGoogleLocation::factory()->create();

        // Create reviews with different ratings
        Review::factory()->create(['location_id' => $location->id, 'star_rating' => 'FIVE']);
        Review::factory()->create(['location_id' => $location->id, 'star_rating' => 'FIVE']);
        Review::factory()->create(['location_id' => $location->id, 'star_rating' => 'FOUR']);
        Review::factory()->create(['location_id' => $location->id, 'star_rating' => 'THREE']);
        Review::factory()->create(['location_id' => $location->id, 'star_rating' => 'ONE']);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $distribution = $response->json('ratingDistribution');

        // Verify chart structure
        $this->assertArrayHasKey('labels', $distribution);
        $this->assertArrayHasKey('datasets', $distribution);

        $data = $distribution['datasets'][0]['data'];

        // Order: [5-star, 4-star, 3-star, 2-star, 1-star]
        $this->assertEquals(2, $data[0]); // 5-star
        $this->assertEquals(1, $data[1]); // 4-star
        $this->assertEquals(1, $data[2]); // 3-star
        $this->assertEquals(0, $data[3]); // 2-star
        $this->assertEquals(1, $data[4]); // 1-star
    }

    public function test_sentiment_trend_categorizes_by_rating(): void
    {
        $location = ReviewGoogleLocation::factory()->create();

        // Create reviews from last 5 days
        $today = now();

        // Day 1: 2 positive, 1 neutral
        Review::factory()->count(2)->create([
            'location_id' => $location->id,
            'star_rating' => 'FIVE',
            'review_time' => $today->copy()->subDays(1),
        ]);
        Review::factory()->create([
            'location_id' => $location->id,
            'star_rating' => 'THREE',
            'review_time' => $today->copy()->subDays(1),
        ]);

        // Day 2: 1 negative
        Review::factory()->create([
            'location_id' => $location->id,
            'star_rating' => 'TWO',
            'review_time' => $today->copy()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $sentimentTrend = $response->json('sentimentTrend');

        $this->assertArrayHasKey('labels', $sentimentTrend);
        $this->assertArrayHasKey('datasets', $sentimentTrend);
        $this->assertCount(3, $sentimentTrend['datasets']); // positive, neutral, negative
    }

    public function test_dashboard_handles_empty_state_gracefully(): void
    {
        // No reviews in database

        $response = $this->actingAs($this->user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $kpis = $response->json('kpis');

        $this->assertEquals(0, $kpis['total']);
        $this->assertEquals(0.0, $kpis['avgRating']);
        $this->assertEquals(0, $kpis['unanswered']);
        $this->assertEquals(0.0, $kpis['responseRate']);

        $this->assertEmpty($response->json('recentReviews'));
        $this->assertEmpty($response->json('attentionNeeded'));
    }

    public function test_unauthorized_access_redirects_to_login(): void
    {
        $response = $this->get(route('reviews.dashboard'));

        $response->assertRedirect(route('auth.login'));
    }
}
