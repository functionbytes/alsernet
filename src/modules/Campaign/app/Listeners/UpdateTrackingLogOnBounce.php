<?php

namespace Modules\Campaign\Listeners;

use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\Campaign\Services\BounceClassifier;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;

/**
 * Listener del módulo Campaign que actualiza campaign_tracking_logs cuando
 * CampaignSendingServers detecta un bounce o feedback loop.
 */
class UpdateTrackingLogOnBounce
{
    public function handleBounce(BounceDetected $event): void
    {
        if (empty($event->messageId)) {
            return;
        }

        $classifier = new BounceClassifier;
        $classification = $classifier->classify($event->messageId);

        CampaignTrackingLog::where('message_id', $event->messageId)
            ->update([
                'status' => CampaignTrackingLog::STATUS_BOUNCED,
                'bounce_type' => $classification['type'],
                'bounce_category' => $classification['category'],
            ]);
    }

    public function handleFeedback(FeedbackLoopDetected $event): void
    {
        if (empty($event->messageId)) {
            return;
        }

        CampaignTrackingLog::where('message_id', $event->messageId)
            ->update(['status' => CampaignTrackingLog::STATUS_FEEDBACK_ABUSE]);
    }
}
