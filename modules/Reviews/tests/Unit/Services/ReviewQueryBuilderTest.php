<?php

namespace Modules\Reviews\Tests\Unit\Services;

use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewQueryBuilder;
use Modules\Reviews\Tests\TestCase;

class ReviewQueryBuilderTest extends TestCase
{
    public function test_apply_filters_by_location_id(): void
    {
        $locationA = $this->createLocation();
        $locationB = $this->createLocation();

        Review::factory()->for($locationA, 'location')->create();
        Review::factory()->for($locationA, 'location')->create();
        Review::factory()->for($locationB, 'location')->create();

        $results = ReviewQueryBuilder::applyFilters(Review::query(), [
            'location_id' => $locationA->id,
        ])->get();

        $this->assertCount(2, $results);
        $results->each(fn ($r) => $this->assertSame($locationA->id, $r->location_id));
    }

    public function test_apply_filters_has_reply_true_returns_only_reviews_with_google_reply(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->withGoogleReply()->create();
        Review::factory()->for($location, 'location')->withGoogleReply()->create();
        Review::factory()->for($location, 'location')->create(['google_reply_text' => null]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['has_reply' => true])->get();

        $this->assertCount(2, $results);
        $results->each(fn ($r) => $this->assertNotNull($r->google_reply_text));
    }

    public function test_apply_filters_has_reply_false_returns_only_reviews_without_google_reply(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->withGoogleReply()->create();
        Review::factory()->for($location, 'location')->create(['google_reply_text' => null]);
        Review::factory()->for($location, 'location')->create(['google_reply_text' => null]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['has_reply' => false])->get();

        $this->assertCount(2, $results);
        $results->each(fn ($r) => $this->assertNull($r->google_reply_text));
    }

    public function test_apply_filters_by_is_visible_true(): void
    {
        $location = $this->createLocation();

        $visibleReview = Review::factory()->for($location, 'location')->create();
        $hiddenReview = Review::factory()->for($location, 'location')->create();

        $this->createModeration($visibleReview, ['is_visible' => true]);
        $this->createModeration($hiddenReview, ['is_visible' => false]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['is_visible' => true])->get();

        $this->assertCount(1, $results);
        $this->assertSame($visibleReview->id, $results->first()->id);
    }

    public function test_apply_filters_by_is_visible_false(): void
    {
        $location = $this->createLocation();

        $visibleReview = Review::factory()->for($location, 'location')->create();
        $hiddenReview = Review::factory()->for($location, 'location')->create();

        $this->createModeration($visibleReview, ['is_visible' => true]);
        $this->createModeration($hiddenReview, ['is_visible' => false]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['is_visible' => false])->get();

        $this->assertCount(1, $results);
        $this->assertSame($hiddenReview->id, $results->first()->id);
    }

    public function test_apply_filters_date_from_excludes_older_reviews(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDays(10)]);
        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDays(3)]);
        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDay()]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), [
            'date_from' => now()->subDays(5)->toDateTimeString(),
        ])->get();

        $this->assertCount(2, $results);
    }

    public function test_apply_filters_date_to_excludes_newer_reviews(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDays(10)]);
        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDays(3)]);
        Review::factory()->for($location, 'location')->create(['review_time' => now()->subDay()]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), [
            'date_to' => now()->subDays(5)->toDateTimeString(),
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_apply_filters_search_matches_reviewer_name(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->create(['reviewer_name' => 'John Doe']);
        Review::factory()->for($location, 'location')->create(['reviewer_name' => 'Jane Smith']);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['search' => 'John'])->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('John', $results->first()->reviewer_name);
    }

    public function test_apply_filters_search_matches_comment(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->create([
            'reviewer_name' => 'Alice',
            'comment' => 'Excellent service provided',
        ]);
        Review::factory()->for($location, 'location')->create([
            'reviewer_name' => 'Bob',
            'comment' => 'Average experience',
        ]);

        $results = ReviewQueryBuilder::applyFilters(Review::query(), ['search' => 'Excellent'])->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Excellent', $results->first()->comment);
    }

    public function test_apply_filters_with_no_filters_returns_all_reviews(): void
    {
        $location = $this->createLocation();

        Review::factory()->for($location, 'location')->count(3)->create();

        $results = ReviewQueryBuilder::applyFilters(Review::query(), [])->get();

        $this->assertCount(3, $results);
    }
}
