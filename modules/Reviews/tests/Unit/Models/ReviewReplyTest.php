<?php

namespace Modules\Reviews\Tests\Unit\Models;

use App\Models\User;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Tests\TestCase;

class ReviewReplyTest extends TestCase
{
    public function test_reply_belongs_to_review(): void
    {
        $review = Review::factory()->create();
        $reply = ReviewReply::factory()->for($review)->create();

        $this->assertInstanceOf(Review::class, $reply->review);
        $this->assertTrue($reply->review->is($review));
    }

    public function test_reply_belongs_to_created_by_user(): void
    {
        $user = User::factory()->create();
        $reply = ReviewReply::factory()->for($user, 'createdBy')->create();

        $this->assertInstanceOf(User::class, $reply->createdBy);
        $this->assertTrue($reply->createdBy->is($user));
    }

    public function test_reply_belongs_to_approved_by_user(): void
    {
        $user = User::factory()->create();
        $reply = ReviewReply::factory()
            ->for($user, 'approvedBy')
            ->state(['status' => 'approved', 'approved_at' => now()])
            ->create();

        $this->assertInstanceOf(User::class, $reply->approvedBy);
        $this->assertTrue($reply->approvedBy->is($user));
    }

    public function test_status_enum_casting_works(): void
    {
        $reply = ReviewReply::factory()->create(['status' => ReplyStatus::DRAFT]);

        $this->assertInstanceOf(ReplyStatus::class, $reply->status);
        $this->assertSame(ReplyStatus::DRAFT, $reply->status);
    }

    public function test_draft_scope_filters_draft_replies(): void
    {
        $draftReply = ReviewReply::factory()->draft()->create();
        ReviewReply::factory()->approved()->create();

        $results = ReviewReply::draft()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($draftReply));
    }

    public function test_approved_scope_filters_approved_replies(): void
    {
        ReviewReply::factory()->draft()->create();
        $approvedReply = ReviewReply::factory()->approved()->create();

        $results = ReviewReply::approved()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($approvedReply));
    }

    public function test_published_scope_filters_published_replies(): void
    {
        ReviewReply::factory()->draft()->create();
        $publishedReply = ReviewReply::factory()->published()->create();

        $results = ReviewReply::published()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($publishedReply));
    }

    public function test_failed_scope_filters_failed_replies(): void
    {
        ReviewReply::factory()->draft()->create();
        $failedReply = ReviewReply::factory()->failed()->create();

        $results = ReviewReply::failed()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($failedReply));
    }

    public function test_is_draft_returns_true_for_draft_status(): void
    {
        $reply = ReviewReply::factory()->draft()->create();

        $this->assertTrue($reply->isDraft());
    }

    public function test_is_approved_returns_true_for_approved_status(): void
    {
        $reply = ReviewReply::factory()->approved()->create();

        $this->assertTrue($reply->isApproved());
    }

    public function test_is_published_returns_true_for_published_status(): void
    {
        $reply = ReviewReply::factory()->published()->create();

        $this->assertTrue($reply->isPublished());
    }

    public function test_is_failed_returns_true_for_failed_status(): void
    {
        $reply = ReviewReply::factory()->failed()->create();

        $this->assertTrue($reply->isFailed());
    }

    public function test_mark_as_approved_updates_status_and_metadata(): void
    {
        $user = User::factory()->create();
        $reply = ReviewReply::factory()->draft()->create();

        $reply->markAsApproved($user);

        $this->assertSame(ReplyStatus::APPROVED, $reply->fresh()->status);
        $this->assertTrue($reply->fresh()->approvedBy->is($user));
        $this->assertNotNull($reply->fresh()->approved_at);
    }

    public function test_mark_as_published_updates_status_and_timestamp(): void
    {
        $reply = ReviewReply::factory()->approved()->create();

        $reply->markAsPublished();

        $this->assertSame(ReplyStatus::PUBLISHED, $reply->fresh()->status);
        $this->assertNotNull($reply->fresh()->published_at);
    }

    public function test_mark_as_failed_updates_status_and_error_info(): void
    {
        $reply = ReviewReply::factory()->approved()->create(['error_count' => 0]);

        $reply->markAsFailed('API error occurred');

        $reply = $reply->fresh();
        $this->assertSame(ReplyStatus::FAILED, $reply->status);
        $this->assertSame('API error occurred', $reply->error_message);
        $this->assertSame(1, $reply->error_count);
    }

    public function test_mark_as_failed_increments_error_count(): void
    {
        $reply = ReviewReply::factory()->failed()->create(['error_count' => 2]);

        $reply->markAsFailed('Another error');

        $this->assertSame(3, $reply->fresh()->error_count);
    }

    public function test_status_scope_filters_by_status(): void
    {
        $draftReply = ReviewReply::factory()->draft()->create();
        ReviewReply::factory()->published()->create();

        $results = ReviewReply::status(ReplyStatus::DRAFT)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($draftReply));
    }
}
