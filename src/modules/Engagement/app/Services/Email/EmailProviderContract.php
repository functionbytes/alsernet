<?php

declare(strict_types=1);

namespace Modules\Engagement\Services\Email;

use Modules\Engagement\Models\EmailCampaign;

interface EmailProviderContract
{
    /**
     * Send or schedule a campaign through the provider.
     *
     * @return array{provider_campaign_id: string, status: string}
     *
     * @throws \RuntimeException on provider error
     */
    public function sendCampaign(EmailCampaign $campaign): array;

    /**
     * Fetch aggregated stats from the provider.
     *
     * @return array{sent: int, opened: int, clicked: int, bounced: int, unsubscribed: int}
     */
    public function getCampaignStats(string $providerCampaignId): array;

    /**
     * Validate that the provider connection is healthy.
     */
    public function healthCheck(): bool;
}
