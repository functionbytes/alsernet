<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;

/**
 * Posts campaign lifecycle events to any webhook subscribers configured under
 * config('helpdeskcampaigns.webhooks').
 *
 * Each webhook is { url: string, secret?: string, events?: string[] }.
 * Payload format mirrors GitHub-style webhooks: { event, data, timestamp }.
 */
class DispatchCampaignWebhooks implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 60;

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

            $this->deliver($webhook, $payload);
        }
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
        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['https', 'http'], true)) {
            return false;
        }

        $host = $parsed['host'] ?? '';

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        $blocked = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];

        return ! in_array(strtolower($host), $blocked, true);
    }

    private function deliver(array $webhook, array $payload): void
    {
        $headers = ['Content-Type' => 'application/json'];

        if (! empty($webhook['secret'])) {
            $signature = hash_hmac('sha256', json_encode($payload), $webhook['secret']);
            $headers['X-Helpdesk-Signature'] = "sha256={$signature}";
        }

        try {
            Http::timeout(8)
                ->withHeaders($headers)
                ->post($webhook['url'], $payload);
        } catch (\Throwable $e) {
            Log::warning('Campaign webhook delivery failed', [
                'host' => parse_url($webhook['url'], PHP_URL_HOST),
                'event' => $payload['event'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
