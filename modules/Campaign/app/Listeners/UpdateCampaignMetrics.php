<?php

namespace Modules\Campaign\Listeners;

use Modules\Campaign\Events\CampaignMessageClicked;
use Modules\Campaign\Events\CampaignMessageOpened;
use Modules\Campaign\Events\CampaignMessageSent;
use Modules\Campaign\Events\CampaignStatsUpdated;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;

/**
 * Actualiza columnas materializadas de métricas en la campaña.
 * Se ejecuta síncronamente para garantizar consistencia inmediata.
 */
class UpdateCampaignMetrics
{
    public function handleSent(CampaignMessageSent $event): void
    {
        $event->campaign->increment('sent_count');
        $this->broadcastStats($event->campaign);
    }

    public function handleOpened(CampaignMessageOpened $event): void
    {
        $event->campaign->increment('open_count');
        $this->broadcastStats($event->campaign);
    }

    public function handleClicked(CampaignMessageClicked $event): void
    {
        $event->campaign->increment('click_count');
        $this->broadcastStats($event->campaign);
    }

    public function handleBounce(BounceDetected $event): void
    {
        $campaign = $this->findCampaignByMessageId($event->messageId);
        if ($campaign) {
            $campaign->increment('bounce_count');
        }
    }

    public function handleFeedback(FeedbackLoopDetected $event): void
    {
        $campaign = $this->findCampaignByMessageId($event->messageId);
        if ($campaign) {
            $campaign->increment('feedback_count');
        }
    }

    protected function findCampaignByMessageId(?string $messageId): ?Campaign
    {
        if (empty($messageId)) {
            return null;
        }

        $log = CampaignTrackingLog::where('runtime_message_id', $messageId)
            ->orWhere('message_id', $messageId)
            ->first();

        return $log?->campaign;
    }

    protected function broadcastStats(Campaign $campaign): void
    {
        try {
            CampaignStatsUpdated::dispatch($campaign, [
                'sent_count' => $campaign->sent_count,
                'open_count' => $campaign->open_count,
                'click_count' => $campaign->click_count,
                'bounce_count' => $campaign->bounce_count,
                'unsubscribe_count' => $campaign->unsubscribe_count,
                'feedback_count' => $campaign->feedback_count,
                'failed_count' => $campaign->failed_count,
            ]);
        } catch (\Throwable) {
            // Silenciar errores de broadcast
        }
    }
}
