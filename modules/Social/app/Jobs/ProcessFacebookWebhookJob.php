<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Social\Models\SocialAccount;

class ProcessFacebookWebhookJob implements ShouldQueue
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
        Log::info("Processing Facebook webhook for account {$this->socialAccount->id}", [
            'field' => $this->field,
            'value' => $this->value,
        ]);

        // Process different field types
        match ($this->field) {
            'feed' => $this->processFeedEvent(),
            'comments' => $this->processCommentEvent(),
            'reactions' => $this->processReactionEvent(),
            'messages' => $this->processMessageEvent(),
            default => Log::info("Unhandled Facebook webhook field: {$this->field}"),
        };
    }

    /**
     * Process feed events (new posts, updates).
     */
    protected function processFeedEvent(): void
    {
        $postId = $this->value['post_id'] ?? null;
        $verb = $this->value['verb'] ?? null; // add, remove, edited

        Log::info('Facebook feed event', [
            'post_id' => $postId,
            'verb' => $verb,
        ]);

        // TODO: Update post status, sync data
        // Example: Find post by external_id and update
    }

    /**
     * Process comment events.
     */
    protected function processCommentEvent(): void
    {
        $commentId = $this->value['comment_id'] ?? null;
        $parentId = $this->value['parent_id'] ?? null;
        $message = $this->value['message'] ?? null;
        $from = $this->value['from'] ?? [];

        Log::info('Facebook comment event', [
            'comment_id' => $commentId,
            'parent_id' => $parentId,
            'from' => $from['name'] ?? null,
        ]);

        // TODO: Store comment in database, notify user
        // Example: Create Comment model, send notification
    }

    /**
     * Process reaction events (likes).
     */
    protected function processReactionEvent(): void
    {
        $reactionType = $this->value['reaction_type'] ?? null;
        $postId = $this->value['post_id'] ?? null;

        Log::info('Facebook reaction event', [
            'post_id' => $postId,
            'reaction_type' => $reactionType,
        ]);

        // TODO: Update post reaction counts
    }

    /**
     * Process message events.
     */
    protected function processMessageEvent(): void
    {
        $senderId = $this->value['sender']['id'] ?? null;
        $messageText = $this->value['message']['text'] ?? null;

        Log::info('Facebook message event', [
            'sender_id' => $senderId,
            'message' => $messageText,
        ]);

        // TODO: Store message, create conversation
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFacebookWebhookJob failed permanently', [
            'social_account_id' => $this->socialAccount->id,
            'field' => $this->field,
            'error' => $exception->getMessage(),
        ]);
    }
}
