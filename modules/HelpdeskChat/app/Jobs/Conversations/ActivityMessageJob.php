<?php

namespace Modules\HelpdeskChat\Jobs\Conversations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\HelpdeskChat\Events\Messages\MessageSent;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ActivityMessageJob implements ShouldQueue
{
    use Queueable;

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
}
