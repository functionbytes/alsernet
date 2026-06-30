<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

class ExecuteChatFlowNodeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(
        private readonly int $sessionId,
        private readonly string $message
    ) {
        $this->onQueue('chatflow');
    }

    public function handle(ChatFlowEngine $engine): void
    {
        $session = ChatFlowSession::with('chatFlow', 'conversation')->find($this->sessionId);

        if (! $session || ! $session->isActive()) {
            return;
        }

        $engine->processMessage($session, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExecuteChatFlowNodeJob failed', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
