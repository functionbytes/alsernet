<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\HelpdeskChatFlow\Jobs\HandleNodeTimeoutJob;
use Modules\HelpdeskChatFlow\Jobs\ResumeChatFlowAfterDelayJob;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Timers of a running flow: the inactivity timeout of waiting nodes and the
 * resume of `delay` nodes. Both are queued jobs that call back into the engine.
 */
class ChatFlowScheduler
{
    /**
     * Schedule a timeout job for a waiting node so the bot reacts if the customer
     * goes quiet. No-op unless the node is a wait node with timeout_minutes set.
     */
    public function scheduleTimeout(ChatFlowSession $session, array $node): void
    {
        $minutes = (int) ($node['data']['timeout_minutes'] ?? 0);

        if ($minutes < 1 || ! in_array($node['type'], ChatFlowEngine::PAUSE_TYPES, true)) {
            return;
        }

        $lastItemId = (int) ($session->conversation?->items()->max('id') ?? 0);

        HandleNodeTimeoutJob::dispatch($session->id, $node['id'], $lastItemId, $session->conversation_id)
            ->delay(now()->addMinutes($minutes));
    }

    /**
     * Resume the run at `$nextNodeId` after the `delay` node's `data.seconds`
     * (default 5s, clamped 1-300s to match the editor's input range).
     *
     * @param  array<string, mixed>  $node
     */
    public function scheduleResume(ChatFlowSession $session, array $node, string $nextNodeId): void
    {
        $seconds = max(1, min(300, (int) ($node['data']['seconds'] ?? 5)));

        ResumeChatFlowAfterDelayJob::dispatch($session->id, $nextNodeId, $session->conversation_id)
            ->delay(now()->addSeconds($seconds));
    }
}
