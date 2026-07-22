<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AutoAssignmentService;

/**
 * Retries auto-assignment after the configured delay (#78) when no agent was
 * available at creation time. One attempt: assigns if an agent is now free,
 * otherwise applies the fallback. No-op if the conversation was assigned meanwhile.
 */
class ReattemptAutoAssignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly int $conversationId,
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(AutoAssignmentService $service): void
    {
        $conversation = Conversation::query()->find($this->conversationId);

        if (! $conversation || $conversation->assignee_id || $conversation->group_id) {
            return;
        }

        $userId = $service->resolveAgent($conversation);

        if ($userId) {
            $conversation->assignTo($userId);

            return;
        }

        $service->applyFallback($conversation);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ReattemptAutoAssignJob failed', [
            'conversation_id' => $this->conversationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
