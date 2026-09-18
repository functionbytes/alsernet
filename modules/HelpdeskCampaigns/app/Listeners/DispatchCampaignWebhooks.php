<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;
use Modules\HelpdeskCampaigns\Jobs\DeliverCampaignWebhookJob;

/**
 * Posts campaign lifecycle events to any webhook subscribers configured under
 * config('helpdeskcampaigns.webhooks').
 *
 * Each webhook is { url: string, secret?: string, events?: string[] }.
 * Payload format mirrors GitHub-style webhooks: { event, data, timestamp }.
 *
 * La entrega real va en DeliverCampaignWebhookJob (un job por suscriptor) para
 * que el reintento de un endpoint caído no re-entregue a los demás.
 */
class DispatchCampaignWebhooks implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function viaQueue(): string
    {
        return 'webhooks';
    }

    public function handle(CampaignPublished|CampaignPaused|CampaignResumed|CampaignEnded $event): void
    {
        $webhooks = (array) config('helpdeskcampaigns.webhooks', []);

        if (empty($webhooks)) {
            return;
        }

        $eventName = match (true) {
            $event instanceof CampaignPublished => 'campaign.published',
            $event instanceof CampaignPaused => 'campaign.paused',
            $event instanceof CampaignResumed => 'campaign.resumed',
            $event instanceof CampaignEnded => 'campaign.ended',
        };

        $payload = [
            'event' => $eventName,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $event->campaign->id,
                'name' => $event->campaign->name,
                'type' => $event->campaign->type,
                'status' => $event->campaign->status,
                'impressions_count' => $event->campaign->impressions_count ?? 0,
                'clicks_count' => $event->campaign->clicks_count ?? 0,
            ],
        ];

        foreach ($webhooks as $webhook) {
            if (! $this->shouldDeliver($webhook, $eventName)) {
                continue;
            }

            DeliverCampaignWebhookJob::dispatch(
                $webhook['url'],
                $webhook['secret'] ?? null,
                $payload,
            );
        }
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('DispatchCampaignWebhooks listener failed', [
            'campaign_id' => $event->campaign->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }

    private function shouldDeliver(array $webhook, string $eventName): bool
    {
        if (empty($webhook['url'])) {
            return false;
        }

        if (! $this->isSafeUrl($webhook['url'])) {
            Log::warning('Campaign webhook skipped — unsafe URL', [
                'host' => parse_url($webhook['url'], PHP_URL_HOST),
            ]);

            return false;
        }

        $allowed = $webhook['events'] ?? null;

        return $allowed === null || in_array('*', $allowed, true) || in_array($eventName, $allowed, true);
    }

    private function isSafeUrl(string $url): bool
    {
        // Resuelve DNS y bloquea loopback/RFC1918/link-local; el chequeo
        // anterior solo cubría IPs literales y dejaba pasar hostnames que
        // resolvían a rangos internos.
        return OutboundUrlGuard::isSafe($url);
    }
}
