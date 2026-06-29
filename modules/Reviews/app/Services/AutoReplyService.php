<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Jobs\ApplyAutoReplyJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoReplyRule;
use Modules\Reviews\Models\ReviewReply;

class AutoReplyService
{
    /**
     * Process all active auto-reply rules for a newly synced review.
     * Applies the first matching rule (first-match wins).
     */
    public function processReview(Review $review): void
    {
        // Skip if the review already has a published reply
        if ($review->hasGoogleReply()) {
            return;
        }

        $alreadyReplied = ReviewReply::query()
            ->where('review_id', $review->id)
            ->where('status', ReplyStatus::PUBLISHED)
            ->exists();

        if ($alreadyReplied) {
            return;
        }

        $rules = ReviewAutoReplyRule::query()
            ->with('template')
            ->active()
            ->where('location_id', $review->location_id)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->matchesReview($review)) {
                continue;
            }

            if ($rule->delay_minutes > 0) {
                ApplyAutoReplyJob::dispatch($rule, $review)
                    ->delay(now()->addMinutes($rule->delay_minutes));
            } else {
                $this->applyRule($rule, $review);
            }

            activity()
                ->performedOn($review)
                ->log("Auto-reply applied via rule: {$rule->name}");

            // First-match wins — stop after first matching rule
            return;
        }
    }

    /**
     * Create and publish the reply for a specific rule/review pair.
     * Called directly (delay=0) or from the queued job.
     */
    public function applyRule(ReviewAutoReplyRule $rule, Review $review): void
    {
        $rule->loadMissing('template');

        $replyText = $rule->resolveReplyText($review);

        if (empty($replyText)) {
            Log::warning('AutoReplyService: rule has no reply text, skipping', [
                'rule_id' => $rule->id,
                'review_id' => $review->id,
            ]);

            return;
        }

        $reply = ReviewReply::query()->create([
            'review_id' => $review->id,
            'reply_text' => $replyText,
            'status' => ReplyStatus::DRAFT,
            'created_by' => null,
        ]);

        try {
            app(ReviewReplyService::class)->publish($reply, null);
        } catch (\Throwable $e) {
            Log::error('AutoReplyService: failed to publish auto-reply', [
                'rule_id' => $rule->id,
                'review_id' => $review->id,
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getRulesForLocation(int $locationId): Collection
    {
        return ReviewAutoReplyRule::query()
            ->with('template')
            ->where('location_id', $locationId)
            ->orderBy('id')
            ->get();
    }

    public function createRule(array $data): ReviewAutoReplyRule
    {
        return ReviewAutoReplyRule::query()->create($data);
    }

    public function toggleRule(ReviewAutoReplyRule $rule): ReviewAutoReplyRule
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        return $rule->fresh();
    }
}
