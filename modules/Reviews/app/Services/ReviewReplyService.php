<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewReplyService
{
    public function createDraft(Review $review, string $replyText, User $user): ReviewReply
    {
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

        $reply->update(['reply_text' => $replyText]);

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply draft updated');

        return $reply->fresh();
    }

    public function approve(ReviewReply $reply, User $user): ReviewReply
    {
        if (! $reply->isDraft()) {
            throw new \Exception('Only draft replies can be approved');
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

    public function publish(ReviewReply $reply, User $user): ReviewReply
    {
        if (! $reply->isApproved() && ! $reply->isDraft()) {
            throw new \Exception('Only approved or draft replies can be published');
        }

        DB::transaction(function () use ($reply, $user) {
            if ($reply->isDraft()) {
                $reply->markAsApproved($user);
            }

            app(GoogleReviewService::class)->publishReply($reply);

            $reply->markAsPublished($user);

            event(new ReplyPublished($reply));
        });

        activity()
            ->performedOn($reply)
            ->causedBy($user)
            ->log('Reply published to Google');

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
