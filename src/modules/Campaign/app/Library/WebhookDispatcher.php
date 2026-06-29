<?php

namespace Modules\Campaign\Library;

use Modules\Campaign\Jobs\DispatchCampaignWebhook;
use Modules\Campaign\Models\Campaign;

/**
 * Helper unificado para emitir webhooks de un evento de campaña a todos los
 * webhooks habilitados para ese campaign + event.
 *
 * Uso:
 *   WebhookDispatcher::emit($campaign, 'sent', [
 *       'email' => 'user@example.com',
 *       'message_id' => 'X',
 *   ]);
 */
class WebhookDispatcher
{
    public static function emit(Campaign $campaign, string $event, array $extra = []): void
    {
        $webhooks = $campaign->campaignWebhooks()
            ->where('enabled', true)
            ->where(function ($q) use ($event): void {
                $q->where('event', $event)->orWhere('event', '*');
            })
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = array_merge([
            'event' => $event,
            'campaign_uid' => $campaign->uid,
            'campaign_id' => $campaign->id,
            'timestamp' => now()->toIso8601String(),
        ], $extra);

        foreach ($webhooks as $webhook) {
            DispatchCampaignWebhook::dispatch($webhook->id, $payload)->onQueue('webhooks');
        }
    }
}
