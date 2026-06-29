<?php

declare(strict_types=1);

namespace Modules\Engagement\Services\Email;

use Modules\Engagement\Models\EmailCampaign;

class EmailCampaignService
{
    public function resolveConnector(EmailCampaign $campaign): EmailProviderContract
    {
        return match ($campaign->provider) {
            'mailchimp' => new MailchimpConnector(
                config('engagement.services.mailchimp.api_key', ''),
                config('engagement.services.mailchimp.server_prefix', ''),
            ),
            'sendgrid' => new SendGridConnector(
                config('engagement.services.sendgrid.api_key', ''),
            ),
            default => throw new \InvalidArgumentException("Proveedor '{$campaign->provider}' no soportado."),
        };
    }

    public function send(EmailCampaign $campaign): void
    {
        $connector = $this->resolveConnector($campaign);
        $result = $connector->sendCampaign($campaign);

        $campaign->update([
            'provider_campaign_id' => $result['provider_campaign_id'],
            'status' => $result['status'],
            'sent_at' => now(),
        ]);
    }

    public function syncStats(EmailCampaign $campaign): void
    {
        if (! $campaign->provider_campaign_id) {
            return;
        }

        $connector = $this->resolveConnector($campaign);
        $stats = $connector->getCampaignStats($campaign->provider_campaign_id);

        $campaign->update([
            'sent_count' => $stats['sent'],
            'open_count' => $stats['opened'],
            'click_count' => $stats['clicked'],
            'bounce_count' => $stats['bounced'],
            'unsubscribe_count' => $stats['unsubscribed'],
        ]);
    }

    public function healthCheck(EmailCampaign $campaign): bool
    {
        try {
            return $this->resolveConnector($campaign)->healthCheck();
        } catch (\Throwable) {
            return false;
        }
    }
}
