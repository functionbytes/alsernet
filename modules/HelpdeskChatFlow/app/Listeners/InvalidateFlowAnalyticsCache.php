<?php

namespace Modules\HelpdeskChatFlow\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Http\Controllers\ChatFlowAnalyticsController;

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

        foreach (array_keys(ChatFlowAnalyticsController::RANGE_OPTIONS) as $days) {
            Cache::forget(ChatFlowAnalyticsController::analyticsCacheKey($flowId, $days));
        }
    }
}
