<?php

namespace Modules\Reviews\Tests\Unit\Models;

use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Tests\TestCase;

class ReviewTest extends TestCase
{
    public function test_review_belongs_to_location(): void
    {
        $location = ReviewGoogleLocation::factory()->create();
        $review = Review::factory()->for($location, 'location')->create();

        $this->assertInstanceOf(ReviewGoogleLocation::class, $review->location);
        $this->assertTrue($review->location->is($location));
    }

    public function test_review_has_one_moderation(): void
    {
        $review = Review::factory()->create();
        $moderation = ReviewModeration::factory()->for($review)->create();

        $this->assertInstanceOf(ReviewModeration::class, $review->moderation);
        $this->assertTrue($review->moderation->is($moderation));
    }

    public function test_review_has_many_replies(): void
    {
        $review = Review::factory()->create();
        $reply1 = ReviewReply::factory()->for($review)->create();
        $reply2 = ReviewReply::factory()->for($review)->create();

        $this->assertCount(2, $review->replies);
        $this->assertTrue($review->replies->contains($reply1));
        $this->assertTrue($review->replies->contains($reply2));
    }

    public function test_visible_scope_filters_visible_reviews(): void
    {
        $visibleReview = Review::factory()->create();
        ReviewModeration::factory()->for($visibleReview)->create(['is_visible' => true]);

        $hiddenReview = Review::factory()->create();
        ReviewModeration::factory()->for($hiddenReview)->create(['is_visible' => false]);

        $results = Review::visible()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($visibleReview));
    }

    public function test_featured_scope_filters_featured_reviews(): void
    {
        $featuredReview = Review::factory()->create();
        ReviewModeration::factory()->for($featuredReview)->create(['is_featured' => true]);

        $normalReview = Review::factory()->create();
        ReviewModeration::factory()->for($normalReview)->create(['is_featured' => false]);

        $results = Review::featured()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($featuredReview));
    }

    public function test_rating_scope_filters_by_rating_enum(): void
    {
        $fiveStarReview = Review::factory()->create(['star_rating' => ReviewRating::FIVE]);
        Review::factory()->create(['star_rating' => ReviewRating::THREE]);

        $results = Review::rating(ReviewRating::FIVE)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($fiveStarReview));
    }

    public function test_rating_scope_filters_by_rating_string(): void
    {
        $fourStarReview = Review::factory()->create(['star_rating' => ReviewRating::FOUR]);
        Review::factory()->create(['star_rating' => ReviewRating::TWO]);

        $results = Review::rating('FOUR')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($fourStarReview));
    }

    public function test_with_comment_scope_filters_reviews_with_text(): void
    {
        $withComment = Review::factory()->create(['comment' => 'Great service!']);
        Review::factory()->create(['comment' => null]);

        $results = Review::withComment()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($withComment));
    }

    public function test_without_comment_scope_filters_reviews_without_text(): void
    {
        Review::factory()->create(['comment' => 'Great service!']);
        $withoutComment = Review::factory()->create(['comment' => null]);

        $results = Review::withoutComment()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($withoutComment));
    }

    public function test_with_google_reply_scope(): void
    {
        $withReply = Review::factory()->create(['google_reply_text' => 'Thank you!']);
        Review::factory()->create(['google_reply_text' => null]);

        $results = Review::withGoogleReply()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($withReply));
    }

    public function test_without_google_reply_scope(): void
    {
        Review::factory()->create(['google_reply_text' => 'Thank you!']);
        $withoutReply = Review::factory()->create(['google_reply_text' => null]);

        $results = Review::withoutGoogleReply()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($withoutReply));
    }

    public function test_recent_scope_filters_by_days(): void
    {
        $recentReview = Review::factory()->create(['review_time' => now()->subDays(10)]);
        Review::factory()->create(['review_time' => now()->subDays(40)]);

        $results = Review::recent(30)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($recentReview));
    }

    public function test_is_visible_returns_true_when_moderation_visible(): void
    {
        $review = Review::factory()->create();
        ReviewModeration::factory()->for($review)->create(['is_visible' => true]);

        $this->assertTrue($review->isVisible());
    }

    public function test_is_visible_returns_false_when_moderation_hidden(): void
    {
        $review = Review::factory()->create();
        ReviewModeration::factory()->for($review)->create(['is_visible' => false]);

        $this->assertFalse($review->isVisible());
    }

    public function test_is_featured_returns_true_when_featured(): void
    {
        $review = Review::factory()->create();
        ReviewModeration::factory()->for($review)->create(['is_featured' => true]);

        $this->assertTrue($review->isFeatured());
    }

    public function test_is_featured_returns_false_when_not_featured(): void
    {
        $review = Review::factory()->create();
        ReviewModeration::factory()->for($review)->create(['is_featured' => false]);

        $this->assertFalse($review->isFeatured());
    }

    public function test_has_google_reply_returns_true_when_reply_exists(): void
    {
        $review = Review::factory()->create(['google_reply_text' => 'Thank you!']);

        $this->assertTrue($review->hasGoogleReply());
    }

    public function test_has_google_reply_returns_false_when_no_reply(): void
    {
        $review = Review::factory()->create(['google_reply_text' => null]);

        $this->assertFalse($review->hasGoogleReply());
    }

    public function test_star_rating_value_attribute_returns_numeric_value(): void
    {
        $review = Review::factory()->create(['star_rating' => ReviewRating::FIVE]);

        $this->assertSame(5, $review->star_rating_value);
    }

    public function test_rating_enum_casting_works(): void
    {
        $review = Review::factory()->create(['star_rating' => ReviewRating::FOUR]);

        $this->assertInstanceOf(ReviewRating::class, $review->star_rating);
        $this->assertSame(ReviewRating::FOUR, $review->star_rating);
    }
}
