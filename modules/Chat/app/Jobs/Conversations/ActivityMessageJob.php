<?php

namespace Modules\Chat\Jobs\Conversations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Conversations\Conversation;

class ActivityMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conversation $conversation,
        public array $messageParams
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = $this->conversation->messages()->create($this->messageParams);

        // Broadcast the activity message in real-time
        broadcast(new MessageSent($message))->toOthers();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
