<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

/**
 * Fires when a "wait" node's timeout elapses. If the session is still parked on
 * the same node and the customer hasn't replied since the node was reached, the
 * engine runs the node's timeout branch (re-ask, close, or transfer).
 */
class HandleNodeTimeoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public int $backoff = 0;

    public function __construct(
        private readonly int $sessionId,
        private readonly string $nodeId,
        private readonly int $sinceItemId,
    ) {
        $this->onQueue('chatflow');
    }

    public function handle(ChatFlowEngine $engine): void
    {
        $session = ChatFlowSession::on('helpdesk')
            ->with(['chatFlow', 'conversation'])
            ->find($this->sessionId);

        // Already advanced, ended, or moved to another node → nothing to do.
        if (! $session || ! $session->isActive() || $session->current_node_id !== $this->nodeId) {
            return;
        }

        // The customer replied after the node was reached → not a timeout.
        $replied = $session->conversation?->items()
            ->where('id', '>', $this->sinceItemId)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
            ->exists();

        if ($replied) {
            return;
        }

        $node = $session->chatFlow?->getNodeById($this->nodeId);

        if ($node) {
            $engine->handleNodeTimeout($session, $node);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('HandleNodeTimeoutJob failed', [
            'session_id' => $this->sessionId,
            'node_id' => $this->nodeId,
            'error' => $exception->getMessage(),
        ]);
    }
}
