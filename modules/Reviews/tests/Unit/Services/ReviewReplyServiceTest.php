<?php

namespace Modules\Reviews\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Services\ReviewReplyService;
use Modules\Reviews\Tests\TestCase;

class ReviewReplyServiceTest extends TestCase
{
    private ReviewReplyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReviewReplyService::class);
    }

    public function test_create_draft_creates_reply_with_draft_status(): void
    {
        $review = $this->createReview();
        $user = $this->createUser();

        $reply = $this->service->createDraft($review, 'Thank you!', $user);

        $this->assertDatabaseHas('review_replies', [
            'id' => $reply->id,
            'review_id' => $review->id,
            'reply_text' => 'Thank you!',
            'status' => ReplyStatus::DRAFT->value,
            'created_by' => $user->id,
        ]);
    }

    public function test_update_draft_updates_reply_text(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create([
            'reply_text' => 'Original text',
        ]);

        $updated = $this->service->updateDraft($reply, 'Updated text', $user);

        $this->assertSame('Updated text', $updated->reply_text);
    }

    public function test_update_draft_throws_exception_for_non_draft_reply(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->approved()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only draft replies can be edited');

        $this->service->updateDraft($reply, 'New text', $user);
    }

    public function test_approve_marks_reply_as_approved(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create();

        $approved = $this->service->approve($reply, $user);

        $this->assertSame(ReplyStatus::APPROVED, $approved->status);
        $this->assertTrue($approved->approvedBy->is($user));
        $this->assertNotNull($approved->approved_at);
    }

    public function test_approve_throws_exception_for_non_draft_reply(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->published()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only draft replies can be approved');

        $this->service->approve($reply, $user);
    }

    public function test_approve_dispatches_publish_job_when_auto_publish_enabled(): void
    {
        config(['reviews.general.auto_publish_replies' => true]);

        Queue::fake();

        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create();

        $this->service->approve($reply, $user);

        Queue::assertPushed(PublishReviewReplyJob::class);
    }

    public function test_approve_does_not_dispatch_publish_job_when_auto_publish_disabled(): void
    {
        config(['reviews.general.auto_publish_replies' => false]);

        Queue::fake();

        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create();

        $this->service->approve($reply, $user);

        Queue::assertNotPushed(PublishReviewReplyJob::class);
    }

    public function test_publish_marks_reply_as_published(): void
    {
        $this->fakeGoogleReplySuccess();

        $user = $this->createUser();
        $reply = ReviewReply::factory()->approved()->create();

        Event::fake([ReplyPublished::class]);

        $published = $this->service->publish($reply, $user);

        $this->assertSame(ReplyStatus::PUBLISHED, $published->status);
        $this->assertNotNull($published->published_at);

        Event::assertDispatched(ReplyPublished::class);
    }

    public function test_publish_approves_draft_reply_before_publishing(): void
    {
        $this->fakeGoogleReplySuccess();

        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create();

        $published = $this->service->publish($reply, $user);

        $this->assertSame(ReplyStatus::PUBLISHED, $published->status);
        $this->assertTrue($published->approvedBy->is($user));
    }

    public function test_publish_throws_exception_for_invalid_status(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->failed()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only approved or draft replies can be published');

        $this->service->publish($reply, $user);
    }

    public function test_delete_removes_reply_from_database(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->draft()->create();

        $result = $this->service->delete($reply, $user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('review_replies', ['id' => $reply->id]);
    }

    public function test_delete_calls_google_api_for_published_replies(): void
    {
        $this->fakeGoogleReplySuccess();

        $user = $this->createUser();
        $reply = ReviewReply::factory()->published()->create();

        $this->service->delete($reply, $user);

        $this->assertDatabaseMissing('review_replies', ['id' => $reply->id]);
    }

    public function test_create_from_template_replaces_variables(): void
    {
        $review = $this->createReview();
        $review->reviewer_name = 'John Doe';
        $review->location->name = 'Test Business';
        $review->save();

        $user = $this->createUser();
        $template = ReviewReplyTemplate::factory()->create([
            'content' => 'Thank you {reviewer_name} for your {rating}-star review at {business_name}!',
        ]);

        $reply = $this->service->createFromTemplate($review, $template, $user);

        $expectedText = "Thank you John Doe for your {$review->star_rating->value()}-star review at Test Business!";

        $this->assertStringContainsString('John Doe', $reply->reply_text);
        $this->assertStringContainsString('Test Business', $reply->reply_text);
    }

    public function test_create_from_template_creates_draft_status(): void
    {
        $review = $this->createReview();
        $user = $this->createUser();
        $template = ReviewReplyTemplate::factory()->create([
            'content' => 'Thank you!',
        ]);

        $reply = $this->service->createFromTemplate($review, $template, $user);

        $this->assertSame(ReplyStatus::DRAFT, $reply->status);
        $this->assertTrue($reply->createdBy->is($user));
    }
}
