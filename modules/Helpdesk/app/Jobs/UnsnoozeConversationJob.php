<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationUnsnoozed;
use Modules\Helpdesk\Models\Conversation;

class UnsnoozeConversationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly Conversation $conversation
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(): void
    {
        $conversation = $this->conversation->fresh();

        if (! $conversation || ! $conversation->snoozed_until) {
            return;
        }

        if ($conversation->snoozed_until->greaterThan(now())) {
            return;
        }

        $conversation->update([
            'snoozed_until' => null,
            'snoozed_by' => null,
        ]);

        ConversationUnsnoozed::dispatch($conversation);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('UnsnoozeConversationJob failed', [
            'conversation_id' => $this->conversation->id,
            'error' => $e->getMessage(),
        ]);
    }
}
