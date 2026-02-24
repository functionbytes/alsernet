<?php

namespace Modules\Reviews\Tests\Feature;

use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Tests\TestCase;

class ReviewDashboardTest extends TestCase
{
    public function test_user_can_view_dashboard(): void
    {
        $user = $this->createUser(['reviews.view']);

        $response = $this->actingAs($user)
            ->get(route('reviews.dashboard'));

        $response->assertOk()
            ->assertViewIs('reviews::dashboard.index');
    }

    public function test_dashboard_data_returns_kpi_metrics(): void
    {
        $user = $this->createUser(['reviews.view']);

        $location = ReviewGoogleLocation::factory()->create();
        Review::factory()
            ->for($location, 'location')
            ->count(5)
            ->create([
                'star_rating' => '5_STAR',
                'comment' => 'Great service!',
            ]);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'total',
                    'avgRating',
                    'unanswered',
                    'responseRate',
                ],
                'ratingTrends',
                'locationStats',
                'reviewsByDay',
                'ratingDistribution',
            ]);

        $data = $response->json();
        $this->assertEquals(5, $data['kpis']['total']);
        $this->assertEquals(5.0, $data['kpis']['avgRating']);
    }

    public function test_dashboard_rating_trends_returns_monthly_data(): void
    {
        $user = $this->createUser(['reviews.view']);
        $location = ReviewGoogleLocation::factory()->create();

        Review::factory()
            ->for($location, 'location')
            ->create([
                'star_rating' => '5_STAR',
                'review_time' => now()->subMonth(),
            ]);

        Review::factory()
            ->for($location, 'location')
            ->create([
                'star_rating' => '4_STAR',
                'review_time' => now(),
            ]);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $trends = $response->json('ratingTrends');
        $this->assertNotEmpty($trends['labels']);
        $this->assertNotEmpty($trends['datasets']);
    }

    public function test_dashboard_location_stats_returns_top_locations(): void
    {
        $user = $this->createUser(['reviews.view']);

        $location1 = ReviewGoogleLocation::factory()->create(['name' => 'Location A']);
        $location2 = ReviewGoogleLocation::factory()->create(['name' => 'Location B']);

        Review::factory()->for($location1, 'location')->count(10)->create();
        Review::factory()->for($location2, 'location')->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $stats = $response->json('locationStats');
        $this->assertNotEmpty($stats['labels']);
        $this->assertContains('Location A', $stats['labels']);
        $this->assertContains('Location B', $stats['labels']);
    }

    public function test_dashboard_reviews_by_day_returns_daily_counts(): void
    {
        $user = $this->createUser(['reviews.view']);
        $location = ReviewGoogleLocation::factory()->create();

        Review::factory()
            ->for($location, 'location')
            ->count(3)
            ->create([
                'review_time' => now()->subDays(5),
            ]);

        Review::factory()
            ->for($location, 'location')
            ->count(2)
            ->create([
                'review_time' => now()->subDays(3),
            ]);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $byDay = $response->json('reviewsByDay');
        $this->assertNotEmpty($byDay['labels']);
        $this->assertNotEmpty($byDay['datasets']);
    }

    public function test_dashboard_rating_distribution_returns_all_ratings(): void
    {
        $user = $this->createUser(['reviews.view']);
        $location = ReviewGoogleLocation::factory()->create();

        Review::factory()->for($location, 'location')->count(5)->create(['star_rating' => '5_STAR']);
        Review::factory()->for($location, 'location')->count(3)->create(['star_rating' => '4_STAR']);
        Review::factory()->for($location, 'location')->count(2)->create(['star_rating' => '3_STAR']);
        Review::factory()->for($location, 'location')->count(1)->create(['star_rating' => '2_STAR']);
        Review::factory()->for($location, 'location')->count(1)->create(['star_rating' => '1_STAR']);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $distribution = $response->json('ratingDistribution');
        $this->assertCount(5, $distribution['labels']);
        $this->assertNotEmpty($distribution['datasets']);
    }

    public function test_response_rate_calculated_correctly(): void
    {
        $user = $this->createUser(['reviews.view']);
        $location = ReviewGoogleLocation::factory()->create();

        Review::factory()
            ->for($location, 'location')
            ->count(5)
            ->create([
                'comment' => 'Great service!',
                'google_reply_text' => 'Thank you!',
            ]);

        Review::factory()
            ->for($location, 'location')
            ->count(5)
            ->create([
                'comment' => 'Good experience',
                'google_reply_text' => null,
            ]);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.dashboard.data'));

        $response->assertOk();

        $kpis = $response->json('kpis');
        $this->assertEquals(50.0, $kpis['responseRate']);
        $this->assertEquals(5, $kpis['unanswered']);
    }
}
