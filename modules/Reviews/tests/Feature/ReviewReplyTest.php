<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Tests\TestCase;

class ReviewReplyTest extends TestCase
{
    public function test_user_can_create_draft_reply(): void
    {
        $user = $this->createUser(['reviews.reply']);
        $review = $this->createReview();

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.store'), [
                'review_id' => $review->id,
                'reply_text' => 'Thank you for your review!',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'reply_text' => 'Thank you for your review!',
            'status' => ReplyStatus::DRAFT->value,
            'created_by' => $user->id,
        ]);
    }

    public function test_user_can_update_draft_reply(): void
    {
        $user = $this->createUser(['reviews.reply']);
        $reply = ReviewReply::factory()->draft()->for($user, 'createdBy')->create();

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.replies.update', $reply), [
                'reply_text' => 'Updated reply text',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('review_replies', [
            'id' => $reply->id,
            'reply_text' => 'Updated reply text',
        ]);
    }

    public function test_user_can_approve_reply(): void
    {
        $user = $this->createUser(['reviews.approve-replies']);
        $reply = ReviewReply::factory()->draft()->create();

        config(['reviews.general.auto_publish_replies' => false]);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.publish', $reply), [
                'action' => 'approve',
            ]);

        $response->assertOk();

        $reply = $reply->fresh();
        $this->assertSame(ReplyStatus::APPROVED, $reply->status);
        $this->assertTrue($reply->approvedBy->is($user));
        $this->assertNotNull($reply->approved_at);
    }

    public function test_user_can_publish_reply(): void
    {
        $this->fakeGoogleReplySuccess();

        $user = $this->createUser(['reviews.publish-replies']);
        $reply = ReviewReply::factory()->approved()->create();

        Event::fake([ReplyPublished::class]);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.publish', $reply));

        $response->assertOk();

        $reply = $reply->fresh();
        $this->assertSame(ReplyStatus::PUBLISHED, $reply->status);
        $this->assertNotNull($reply->published_at);

        Event::assertDispatched(ReplyPublished::class);
    }

    public function test_publish_dispatches_job_when_auto_publish_enabled(): void
    {
        config(['reviews.general.auto_publish_replies' => true]);

        Queue::fake();

        $user = $this->createUser(['reviews.approve-replies']);
        $reply = ReviewReply::factory()->draft()->create();

        $service = app(\Modules\Reviews\Services\ReviewReplyService::class);
        $service->approve($reply, $user);

        Queue::assertPushed(PublishReviewReplyJob::class);
    }

    public function test_publish_job_calls_google_api(): void
    {
        $this->fakeGoogleReplySuccess();

        $reply = ReviewReply::factory()->approved()->create();

        PublishReviewReplyJob::dispatchSync($reply, $this->createUser());

        $this->assertDatabaseHas('reviews', [
            'id' => $reply->review_id,
            'google_reply_text' => $reply->reply_text,
        ]);
    }

    public function test_publish_job_handles_google_error(): void
    {
        $this->fakeGoogleReplyError();

        $reply = ReviewReply::factory()->approved()->create();

        try {
            PublishReviewReplyJob::dispatchSync($reply, $this->createUser());
        } catch (\Exception $e) {
            // Expected exception
        }

        $reply = $reply->fresh();
        $this->assertSame(ReplyStatus::FAILED, $reply->status);
        $this->assertNotNull($reply->error_message);
        $this->assertGreaterThan(0, $reply->error_count);
    }

    public function test_reply_status_transitions(): void
    {
        $this->fakeGoogleReplySuccess();

        $user = $this->createUser(['reviews.publish-replies']);
        $reply = ReviewReply::factory()->draft()->create();

        $this->assertSame(ReplyStatus::DRAFT, $reply->status);

        $reply->markAsApproved($user);
        $this->assertSame(ReplyStatus::APPROVED, $reply->fresh()->status);

        $service = app(\Modules\Reviews\Services\ReviewReplyService::class);
        $service->publish($reply->fresh(), $user);
        $this->assertSame(ReplyStatus::PUBLISHED, $reply->fresh()->status);
    }

    public function test_template_variables_replaced(): void
    {
        $user = $this->createUser(['reviews.reply']);
        $review = $this->createReview();
        $review->reviewer_name = 'Jane Doe';
        $review->location->name = 'My Business';
        $review->save();

        $template = \Modules\Reviews\Models\ReviewReplyTemplate::factory()->create([
            'body' => 'Hello {reviewer_name}, thank you for visiting {business_name}!',
        ]);

        $service = app(\Modules\Reviews\Services\ReviewReplyService::class);
        $reply = $service->createFromTemplate($review, $template, $user);

        $this->assertStringContainsString('Jane Doe', $reply->reply_text);
        $this->assertStringContainsString('My Business', $reply->reply_text);
    }

    public function test_unauthorized_user_cannot_publish(): void
    {
        $user = $this->createUser();
        $reply = ReviewReply::factory()->approved()->create();

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.publish', $reply));

        $response->assertForbidden();
    }

    public function test_user_can_delete_draft_reply(): void
    {
        $user = $this->createUser(['reviews.reply']);
        $reply = ReviewReply::factory()->draft()->for($user, 'createdBy')->create();

        $response = $this->actingAs($user)
            ->deleteJson(route('reviews.replies.destroy', $reply));

        $response->assertOk();

        $this->assertDatabaseMissing('review_replies', ['id' => $reply->id]);
    }
}
