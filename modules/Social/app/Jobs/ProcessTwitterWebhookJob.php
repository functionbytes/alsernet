<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Social\Models\SocialAccount;

class ProcessTwitterWebhookJob implements ShouldQueue
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
        public string $eventType,
        public array $eventData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing Twitter webhook for account {$this->socialAccount->id}", [
            'event_type' => $this->eventType,
            'event_data' => $this->eventData,
        ]);

        // Process different event types
        match ($this->eventType) {
            'tweet_create' => $this->processTweetCreate(),
            'favorite' => $this->processFavorite(),
            'direct_message' => $this->processDirectMessage(),
            'tweet_delete' => $this->processTweetDelete(),
            default => Log::info("Unhandled Twitter webhook event: {$this->eventType}"),
        };
    }

    /**
     * Process tweet create event.
     */
    protected function processTweetCreate(): void
    {
        $tweetId = $this->eventData['id_str'] ?? null;
        $text = $this->eventData['text'] ?? null;
        $userId = $this->eventData['user']['id_str'] ?? null;

        Log::info('Twitter tweet create', [
            'tweet_id' => $tweetId,
            'user_id' => $userId,
        ]);

        // TODO: Sync published tweet data
    }

    /**
     * Process favorite event.
     */
    protected function processFavorite(): void
    {
        $tweetId = $this->eventData['favorited_status']['id_str'] ?? null;
        $userId = $this->eventData['user']['id_str'] ?? null;

        Log::info('Twitter favorite', [
            'tweet_id' => $tweetId,
            'favorited_by' => $userId,
        ]);

        // TODO: Update favorite count
    }

    /**
     * Process direct message event.
     */
    protected function processDirectMessage(): void
    {
        $messageId = $this->eventData['id'] ?? null;
        $senderId = $this->eventData['message_create']['sender_id'] ?? null;
        $text = $this->eventData['message_create']['message_data']['text'] ?? null;

        Log::info('Twitter direct message', [
            'message_id' => $messageId,
            'sender_id' => $senderId,
        ]);

        // TODO: Store DM, create conversation
    }

    /**
     * Process tweet delete event.
     */
    protected function processTweetDelete(): void
    {
        $tweetId = $this->eventData['status']['id_str'] ?? null;
        $userId = $this->eventData['status']['user_id_str'] ?? null;

        Log::info('Twitter tweet delete', [
            'tweet_id' => $tweetId,
            'user_id' => $userId,
        ]);

        // TODO: Update post status to deleted
    }
}
