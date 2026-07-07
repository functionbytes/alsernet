<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Social\Models\SocialAccount;

class ProcessInstagramWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    public $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SocialAccount $socialAccount,
        public string $field,
        public array $value
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing Instagram webhook for account {$this->socialAccount->id}", [
            'field' => $this->field,
            'value' => $this->value,
        ]);

        // Process different field types
        match ($this->field) {
            'comments' => $this->processCommentEvent(),
            'mentions' => $this->processMentionEvent(),
            'story_insights' => $this->processStoryInsights(),
            'media' => $this->processMediaEvent(),
            default => Log::info("Unhandled Instagram webhook field: {$this->field}"),
        };
    }

    /**
     * Process comment events.
     */
    protected function processCommentEvent(): void
    {
        $commentId = $this->value['id'] ?? null;
        $mediaId = $this->value['media_id'] ?? null;
        $text = $this->value['text'] ?? null;
        $from = $this->value['from'] ?? [];

        Log::info('Instagram comment event', [
            'comment_id' => $commentId,
            'media_id' => $mediaId,
            'from' => $from['username'] ?? null,
        ]);

        // TODO: Store comment, send notification
    }

    /**
     * Process mention events.
     */
    protected function processMentionEvent(): void
    {
        $commentId = $this->value['comment_id'] ?? null;
        $mediaId = $this->value['media_id'] ?? null;

        Log::info('Instagram mention event', [
            'comment_id' => $commentId,
            'media_id' => $mediaId,
        ]);

        // TODO: Handle mention, notify user
    }

    /**
     * Process story insights.
     */
    protected function processStoryInsights(): void
    {
        $storyId = $this->value['story_id'] ?? null;
        $impressions = $this->value['impressions'] ?? 0;
        $reach = $this->value['reach'] ?? 0;

        Log::info('Instagram story insights', [
            'story_id' => $storyId,
            'impressions' => $impressions,
            'reach' => $reach,
        ]);

        // TODO: Update story stats
    }

    /**
     * Process media events.
     */
    protected function processMediaEvent(): void
    {
        $mediaId = $this->value['id'] ?? null;

        Log::info('Instagram media event', [
            'media_id' => $mediaId,
        ]);

        // TODO: Update media stats
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInstagramWebhookJob failed permanently', [
            'social_account_id' => $this->socialAccount->id,
            'field' => $this->field,
            'error' => $exception->getMessage(),
        ]);
    }
}
