<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Exceptions\ReplyTooLongException;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Notifications\ReplyApprovalRejectedNotification;
use Modules\Reviews\Notifications\ReplyApprovalRequestedNotification;

class ReviewReplyService
{
    private function assertWithinGoogleLimit(string $replyText): void
    {
        $length = mb_strlen($replyText);
        if ($length > ReviewReply::GOOGLE_MAX_CHARS) {
            throw new ReplyTooLongException($length);
        }
    }

    public function createDraft(Review $review, string $replyText, User $user): ReviewReply
    {
        $this->assertWithinGoogleLimit($replyText);

        $reply = ReviewReply::query()->create([
            'review_id' => $review->id,
            'reply_text' => $replyText,
            'status' => ReplyStatus::DRAFT,
            'created_by' => $user->id,
        ]);

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply draft created');

        return $reply;
    }

    public function updateDraft(ReviewReply $reply, string $replyText, User $user): ReviewReply
    {
        if (! $reply->isDraft()) {
            throw new \Exception('Only draft replies can be edited');
        }

        $this->assertWithinGoogleLimit($replyText);

        $reply->update(['reply_text' => $replyText]);

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply draft updated');

        return $reply->fresh();
    }

    public function approve(ReviewReply $reply, User $user): ReviewReply
    {
        if (! $reply->isDraft() && ! $reply->isPendingApproval()) {
            throw new \Exception('Only draft or pending-approval replies can be approved');
        }

        $reply->markAsApproved($user);

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply approved');

        if (config('reviews.general.auto_publish_replies', false)) {
            PublishReviewReplyJob::dispatch($reply, $user);
        }

        return $reply->fresh();
    }

    public function schedule(ReviewReply $reply, Carbon $scheduledAt, User $user): ReviewReply
    {
        if (! $reply->isApproved()) {
            throw new \Exception('Only approved replies can be scheduled');
        }

        if (! $scheduledAt->isFuture()) {
            throw new \Exception('Scheduled time must be in the future');
        }

        $reply->update([
            'status' => ReplyStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log("Reply scheduled for {$scheduledAt->toDateTimeString()}");

        return $reply->fresh();
    }

    public function publish(ReviewReply $reply, ?User $user): ReviewReply
    {
        if (! $reply->isApproved() && ! $reply->isDraft() && ! $reply->isScheduled()) {
            throw new \Exception('Only approved, draft, or scheduled replies can be published');
        }

        // Idempotency: if already published (published_at set), skip Google API call
        if ($reply->isPublished() && $reply->published_at !== null) {
            return $reply->fresh();
        }

        if ($reply->isDraft() && $user !== null) {
            $reply->markAsApproved($user);
        }

        // Phase 1: mark as FAILED optimistically so any crash leaves a recoverable state
        // (will be overwritten to PUBLISHED on success below)
        try {
            app(GoogleReviewService::class)->publishReply($reply);
        } catch (\Throwable $e) {
            $reply->markAsFailed($e->getMessage());

            Log::error('Failed to publish reply to Google', [
                'reply_id' => $reply->id,
                'review_id' => $reply->review_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // Phase 2: persist success in DB
        try {
            DB::transaction(function () use ($reply, $user) {
                $reply->markAsPublished($user);
                event(new ReplyPublished($reply));
            });
        } catch (\Throwable $e) {
            Log::error('Reply published to Google but DB update failed', [
                'reply_id' => $reply->id,
                'review_id' => $reply->review_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $activity = activity()->performedOn($reply);

        if ($user !== null) {
            $activity->causedBy($user);
        }

        $activity->log('Reply published to Google');

        return $reply->fresh();
    }

    public function delete(ReviewReply $reply, User $user): bool
    {
        if ($reply->isPublished()) {
            app(GoogleReviewService::class)->deleteReply($reply->review);
        }

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply deleted');

        return $reply->delete();
    }

    /**
     * @param  int[]  $replyIds
     * @return array{approved: int, failed: int, errors: list<string>}
     */
    public function bulkApprove(array $replyIds, User $user): array
    {
        $replies = ReviewReply::query()
            ->with(['review.location.connection'])
            ->whereIn('id', $replyIds)
            ->get();

        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($replies as $reply) {
            $connectionUserId = $reply->review->location->connection->user_id ?? null;

            if ($connectionUserId !== $user->id && ! $user->hasRole('super-admin')) {
                $failed++;
                $errors[] = "Reply {$reply->id}: acceso denegado";

                continue;
            }

            try {
                $this->approve($reply, $user);
                $approved++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Reply {$reply->id}: {$e->getMessage()}";
            }
        }

        return compact('approved', 'failed', 'errors');
    }

    /**
     * @param  int[]  $replyIds
     * @return array{approved: int, failed: int, errors: list<string>}
     */
    public function bulkPublish(array $replyIds, User $user): array
    {
        $replies = ReviewReply::query()
            ->with(['review.location.connection'])
            ->whereIn('id', $replyIds)
            ->get();

        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($replies as $reply) {
            $connectionUserId = $reply->review->location->connection->user_id ?? null;

            if ($connectionUserId !== $user->id && ! $user->hasRole('super-admin')) {
                $failed++;
                $errors[] = "Reply {$reply->id}: acceso denegado";

                continue;
            }

            try {
                $this->publish($reply, $user);
                $approved++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Reply {$reply->id}: {$e->getMessage()}";
            }
        }

        return compact('approved', 'failed', 'errors');
    }

    public function requestApproval(ReviewReply $reply, User $requester, string $note = ''): ReviewReply
    {
        if (! $reply->isDraft()) {
            throw new \Exception('Solo los borradores pueden solicitar aprobacion');
        }

        $reply->update([
            'status' => ReplyStatus::PENDING_APPROVAL,
            'approval_requested_at' => now(),
            'approval_requested_by' => $requester->id,
            'approval_note' => $note ?: null,
            'rejection_reason' => null,
        ]);

        activity()
            ->performedOn($reply)
            ->causedBy($requester)
            ->log('Approval requested for reply');

        $supervisors = User::query()
            ->permission('reviews.replies.approve')
            ->get();

        $reply->loadMissing('approvalRequestedBy');

        foreach ($supervisors as $supervisor) {
            $supervisor->notify(new ReplyApprovalRequestedNotification($reply));
        }

        return $reply->fresh();
    }

    public function rejectApproval(ReviewReply $reply, User $supervisor, string $reason): ReviewReply
    {
        if (! $reply->isPendingApproval()) {
            throw new \Exception('Solo las respuestas pendientes de aprobacion pueden ser rechazadas');
        }

        $reply->update([
            'status' => ReplyStatus::DRAFT,
            'rejection_reason' => $reason,
        ]);

        activity()
            ->performedOn($reply)
            ->causedBy($supervisor)
            ->log('Approval rejected for reply');

        $author = $reply->createdBy;

        if ($author !== null) {
            $author->notify(new ReplyApprovalRejectedNotification($reply, $reason));
        }

        return $reply->fresh();
    }

    public function createFromTemplate(
        Review $review,
        ReviewReplyTemplate $template,
        User $user
    ): ReviewReply {
        $replyText = $template->renderForReview($review);
        $template->incrementUsage();

        return $this->createDraft($review, $replyText, $user);
    }
}
