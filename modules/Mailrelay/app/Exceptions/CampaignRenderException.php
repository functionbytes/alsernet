<?php

namespace Modules\Mailrelay\Exceptions;

class CampaignRenderException extends MailrelayException
{
    public static function noHtmlContent(int $campaignId): self
    {
        return new self("Campaign #{$campaignId} has no HTML content");
    }

    public static function templateNotFound(string $template): self
    {
        return new self("Campaign template not found: {$template}");
    }

    public static function renderFailed(string $reason): self
    {
        return new self("Campaign render failed: {$reason}");
    }
}
