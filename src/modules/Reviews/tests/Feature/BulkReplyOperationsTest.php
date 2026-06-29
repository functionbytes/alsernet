<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Tests\TestCase;

class BulkReplyOperationsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Bulk approve
    // -------------------------------------------------------------------------

    public function test_bulk_approve_with_valid_reply_ids_returns_success(): void
    {
        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.approve']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $review = $this->createReview($location);

        $replies = ReviewReply::factory()
            ->draft()
            ->count(3)
            ->for($review)
            ->for($user, 'createdBy')
            ->create();

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-approve'), [
                'reply_ids' => $replies->pluck('id')->all(),
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'approved' => 3,
                'failed' => 0,
            ]);

        foreach ($replies as $reply) {
            $this->assertSame(ReplyStatus::APPROVED, $reply->fresh()->status);
        }
    }

    public function test_bulk_approve_with_more_than_50_ids_returns_422(): void
    {
        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.approve']);

        // Generate 51 fake IDs (they don't need to exist — validation fails before lookup)
        $ids = range(1, 51);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-approve'), [
                'reply_ids' => $ids,
            ]);

        $response->assertUnprocessable();
    }

    public function test_bulk_approve_with_another_users_reply_ids_counts_as_failed(): void
    {
        $userA = $this->createUser(['reviews.reviews.view', 'reviews.replies.approve']);
        $userB = $this->createUser(['reviews.reviews.view']);

        // Create reviews/replies under user B's connection
        $connectionB = $this->createConnection($userB);
        $locationB = $this->createLocation($connectionB);
        $reviewB = $this->createReview($locationB);

        $replies = ReviewReply::factory()
            ->draft()
            ->count(2)
            ->for($reviewB)
            ->for($userB, 'createdBy')
            ->create();

        $response = $this->actingAs($userA)
            ->postJson(route('reviews.replies.bulk-approve'), [
                'reply_ids' => $replies->pluck('id')->all(),
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'approved' => 0,
                'failed' => 2,
            ]);

        // Replies must still be in draft — not approved
        foreach ($replies as $reply) {
            $this->assertSame(ReplyStatus::DRAFT, $reply->fresh()->status);
        }
    }

    public function test_bulk_approve_with_empty_reply_ids_returns_422(): void
    {
        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.approve']);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-approve'), [
                'reply_ids' => [],
            ]);

        $response->assertUnprocessable();
    }

    public function test_bulk_approve_requires_authentication(): void
    {
        $response = $this->postJson(route('reviews.replies.bulk-approve'), [
            'reply_ids' => [1, 2, 3],
        ]);

        $response->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Bulk publish
    // -------------------------------------------------------------------------

    public function test_bulk_publish_inline_call_marks_replies_as_published(): void
    {
        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.publish']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $review = $this->createReview($location);

        $replies = ReviewReply::factory()
            ->approved()
            ->count(2)
            ->for($review)
            ->create();

        $this->fakeGoogleReplySuccess();

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-publish'), [
                'reply_ids' => $replies->pluck('id')->all(),
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        foreach ($replies as $reply) {
            $this->assertSame(ReplyStatus::PUBLISHED, $reply->fresh()->status);
        }
    }

    public function test_bulk_publish_with_more_than_50_ids_returns_422(): void
    {
        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.publish']);

        $ids = range(1, 51);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-publish'), [
                'reply_ids' => $ids,
            ]);

        $response->assertUnprocessable();
    }

    public function test_bulk_publish_with_another_users_replies_counts_as_failed(): void
    {
        $userA = $this->createUser(['reviews.reviews.view', 'reviews.replies.publish']);
        $userB = $this->createUser();

        $connectionB = $this->createConnection($userB);
        $locationB = $this->createLocation($connectionB);
        $reviewB = $this->createReview($locationB);

        $replies = ReviewReply::factory()
            ->approved()
            ->count(2)
            ->for($reviewB)
            ->create();

        $response = $this->actingAs($userA)
            ->postJson(route('reviews.replies.bulk-publish'), [
                'reply_ids' => $replies->pluck('id')->all(),
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'approved' => 0,
                'failed' => 2,
            ]);
    }

    public function test_bulk_publish_with_auto_publish_enabled_queues_jobs(): void
    {
        Queue::fake();

        config(['reviews.general.auto_publish_replies' => true]);

        $user = $this->createUser(['reviews.reviews.view', 'reviews.replies.approve']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $review = $this->createReview($location);

        $replies = ReviewReply::factory()
            ->draft()
            ->count(2)
            ->for($review)
            ->for($user, 'createdBy')
            ->create();

        $this->actingAs($user)
            ->postJson(route('reviews.replies.bulk-approve'), [
                'reply_ids' => $replies->pluck('id')->all(),
            ]);

        Queue::assertPushed(PublishReviewReplyJob::class, 2);
    }

    public function test_bulk_publish_requires_authentication(): void
    {
        $response = $this->postJson(route('reviews.replies.bulk-publish'), [
            'reply_ids' => [1, 2, 3],
        ]);

        $response->assertUnauthorized();
    }
}
