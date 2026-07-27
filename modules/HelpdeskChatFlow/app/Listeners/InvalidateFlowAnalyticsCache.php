<?php

namespace Modules\HelpdeskChatFlow\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowsController;

/**
 * Forgets the cached analytics for a flow whenever one of its sessions finishes,
 * so the dashboard reflects the new session without waiting for the TTL.
 */
class InvalidateFlowAnalyticsCache
{
    public function handle(ChatFlowCompleted $event): void
    {
        $flowId = (int) $event->session->chat_flow_id;

        if ($flowId === 0) {
            return;
        }

        foreach (ChatFlowsController::ANALYTICS_DAY_KEYS as $days) {
            Cache::forget(ChatFlowsController::analyticsCacheKey($flowId, $days));
        }
    }
}
