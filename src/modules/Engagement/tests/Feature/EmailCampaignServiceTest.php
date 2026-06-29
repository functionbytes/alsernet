<?php

namespace Modules\Engagement\Tests\Feature;

use Modules\Engagement\Models\EmailCampaign;
use Modules\Engagement\Services\Email\EmailCampaignService;
use Modules\Engagement\Services\Email\MailchimpConnector;
use Modules\Engagement\Services\Email\SendGridConnector;
use Tests\TestCase;

class EmailCampaignServiceTest extends TestCase
{
    public function test_resolve_connector_throws_on_invalid_provider(): void
    {
        $service = new EmailCampaignService;
        $campaign = $this->createMock(EmailCampaign::class);
        $campaign->method('__get')->with('provider')->willReturn('invalid');

        $this->expectException(\InvalidArgumentException::class);

        $service->resolveConnector($campaign);
    }

    public function test_resolve_connector_returns_mailchimp(): void
    {
        $service = new EmailCampaignService;
        $campaign = $this->createMock(EmailCampaign::class);
        $campaign->method('__get')->with('provider')->willReturn('mailchimp');

        $connector = $service->resolveConnector($campaign);

        $this->assertInstanceOf(MailchimpConnector::class, $connector);
    }

    public function test_resolve_connector_returns_sendgrid(): void
    {
        $service = new EmailCampaignService;
        $campaign = $this->createMock(EmailCampaign::class);
        $campaign->method('__get')->with('provider')->willReturn('sendgrid');

        $connector = $service->resolveConnector($campaign);

        $this->assertInstanceOf(SendGridConnector::class, $connector);
    }
}
