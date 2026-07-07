<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoReplyRule;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\AutoReplyService;

class ApplyAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReviewAutoReplyRule $rule,
        public readonly Review $review,
    ) {
        $this->onQueue('replies');
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['reviews', 'auto-reply'];
    }

    public function handle(AutoReplyService $service): void
    {
        // Idempotency: skip if a published reply already exists
        if ($this->review->hasGoogleReply()) {
            Log::info('ApplyAutoReplyJob: review already has a Google reply, skipping', [
                'rule_id' => $this->rule->id,
                'review_id' => $this->review->id,
            ]);

            return;
        }

        $alreadyReplied = ReviewReply::query()
            ->where('review_id', $this->review->id)
            ->where('status', ReplyStatus::PUBLISHED)
            ->exists();

        if ($alreadyReplied) {
            Log::info('ApplyAutoReplyJob: published reply already exists, skipping', [
                'rule_id' => $this->rule->id,
                'review_id' => $this->review->id,
            ]);

            return;
        }

        $service->applyRule($this->rule, $this->review);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ApplyAutoReplyJob failed permanently', [
            'rule_id' => $this->rule->id,
            'review_id' => $this->review->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
