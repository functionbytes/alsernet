<?php

namespace Modules\Mailrelay\Tests\Unit;

use Modules\Mailrelay\Exceptions\CampaignRenderException;
use Modules\Mailrelay\Exceptions\EmailValidationException;
use Modules\Mailrelay\Exceptions\MailrelayException;
use Modules\Mailrelay\Exceptions\ProviderException;
use Modules\Mailrelay\Exceptions\SyncException;
use Tests\TestCase;

class ExceptionsTest extends TestCase
{
    // -------------------------------------------------------
    // MailrelayException
    // -------------------------------------------------------

    /** @test */
    public function mailrelay_exception_sync_failed(): void
    {
        $e = MailrelayException::syncFailed('timeout reached');

        $this->assertInstanceOf(MailrelayException::class, $e);
        $this->assertEquals('Failed to sync with Mailrelay: timeout reached', $e->getMessage());
    }

    /** @test */
    public function mailrelay_exception_connection_failed(): void
    {
        $e = MailrelayException::connectionFailed('DNS resolution failed');

        $this->assertInstanceOf(MailrelayException::class, $e);
        $this->assertStringContainsString('DNS resolution failed', $e->getMessage());
    }

    /** @test */
    public function mailrelay_exception_connection_failed_with_empty_message(): void
    {
        $e = MailrelayException::connectionFailed();

        $this->assertStringContainsString('Failed to connect to Mailrelay API', $e->getMessage());
    }

    /** @test */
    public function mailrelay_exception_subscriber_not_found(): void
    {
        $e = MailrelayException::subscriberNotFound(42);

        $this->assertEquals('Subscriber not found: 42', $e->getMessage());
    }

    /** @test */
    public function mailrelay_exception_campaign_failed(): void
    {
        $e = MailrelayException::campaignFailed('insufficient credits');

        $this->assertEquals('Campaign operation failed: insufficient credits', $e->getMessage());
    }

    /** @test */
    public function mailrelay_exception_invalid_api_key(): void
    {
        $e = MailrelayException::invalidApiKey();

        $this->assertEquals('Invalid Mailrelay API key', $e->getMessage());
    }

    // -------------------------------------------------------
    // ProviderException
    // -------------------------------------------------------

    /** @test */
    public function provider_exception_connection_failed_with_reason(): void
    {
        $e = ProviderException::providerConnectionFailed('SendGrid', 'Invalid API key');

        $this->assertInstanceOf(ProviderException::class, $e);
        $this->assertInstanceOf(MailrelayException::class, $e);
        $this->assertEquals("Provider 'SendGrid' connection failed: Invalid API key", $e->getMessage());
    }

    /** @test */
    public function provider_exception_connection_failed_without_reason(): void
    {
        $e = ProviderException::providerConnectionFailed('Postmark');

        $this->assertEquals("Provider 'Postmark' connection failed", $e->getMessage());
    }

    /** @test */
    public function provider_exception_sending_failed(): void
    {
        $e = ProviderException::sendingFailed('user@test.com', 'Bounce detected');

        $this->assertEquals('Failed to send email to user@test.com: Bounce detected', $e->getMessage());
    }

    /** @test */
    public function provider_exception_not_configured(): void
    {
        $e = ProviderException::notConfigured();

        $this->assertEquals('No default mail provider is configured', $e->getMessage());
    }

    /** @test */
    public function provider_exception_invalid_recipient(): void
    {
        $e = ProviderException::invalidRecipient('bad-email');

        $this->assertEquals('Invalid recipient email address: bad-email', $e->getMessage());
    }

    /** @test */
    public function provider_exception_recipient_required(): void
    {
        $e = ProviderException::recipientRequired();

        $this->assertEquals('Recipient email address is required', $e->getMessage());
    }

    /** @test */
    public function provider_exception_all_providers_failed(): void
    {
        $errors = [
            'Mailrelay' => 'API timeout',
            'SendGrid' => 'Rate limit exceeded',
            'Postmark' => 'Invalid credentials',
        ];

        $e = ProviderException::allProvidersFailed($errors);

        $this->assertStringContainsString('All providers failed', $e->getMessage());
        $this->assertStringContainsString('Mailrelay: API timeout', $e->getMessage());
        $this->assertStringContainsString('SendGrid: Rate limit exceeded', $e->getMessage());
        $this->assertStringContainsString('Postmark: Invalid credentials', $e->getMessage());
    }

    /** @test */
    public function provider_exception_all_providers_failed_with_single_error(): void
    {
        $e = ProviderException::allProvidersFailed(['Mailtrap' => 'Server error']);

        $this->assertStringContainsString('Mailtrap: Server error', $e->getMessage());
    }

    // -------------------------------------------------------
    // CampaignRenderException
    // -------------------------------------------------------

    /** @test */
    public function campaign_render_exception_no_html_content(): void
    {
        $e = CampaignRenderException::noHtmlContent(77);

        $this->assertInstanceOf(CampaignRenderException::class, $e);
        $this->assertInstanceOf(MailrelayException::class, $e);
        $this->assertEquals('Campaign #77 has no HTML content', $e->getMessage());
    }

    /** @test */
    public function campaign_render_exception_template_not_found(): void
    {
        $e = CampaignRenderException::templateNotFound('holiday-promo');

        $this->assertEquals('Campaign template not found: holiday-promo', $e->getMessage());
    }

    /** @test */
    public function campaign_render_exception_render_failed(): void
    {
        $e = CampaignRenderException::renderFailed('Blade compilation error');

        $this->assertEquals('Campaign render failed: Blade compilation error', $e->getMessage());
    }

    // -------------------------------------------------------
    // EmailValidationException
    // -------------------------------------------------------

    /** @test */
    public function email_validation_exception_invalid_email(): void
    {
        $e = EmailValidationException::invalidEmail('bad@');

        $this->assertInstanceOf(EmailValidationException::class, $e);
        $this->assertEquals('Email format is invalid: bad@', $e->getMessage());
    }

    /** @test */
    public function email_validation_exception_invalid_format(): void
    {
        $e = EmailValidationException::invalidFormat('not-email');

        $this->assertEquals('Email format is invalid: not-email', $e->getMessage());
    }

    /** @test */
    public function email_validation_exception_provider_failed(): void
    {
        $e = EmailValidationException::providerFailed('ZeroBounce');

        $this->assertEquals('Validation provider failed: ZeroBounce', $e->getMessage());
    }

    /** @test */
    public function email_validation_exception_all_providers_failed(): void
    {
        $e = EmailValidationException::allProvidersFailed();

        $this->assertEquals('All validation providers failed', $e->getMessage());
    }

    // -------------------------------------------------------
    // SyncException
    // -------------------------------------------------------

    /** @test */
    public function sync_exception_failed(): void
    {
        $e = SyncException::failed('subscribers', 'API returned 500');

        $this->assertInstanceOf(SyncException::class, $e);
        $this->assertInstanceOf(MailrelayException::class, $e);
        $this->assertEquals('Sync failed for subscribers: API returned 500', $e->getMessage());
    }

    /** @test */
    public function sync_exception_timeout(): void
    {
        $e = SyncException::timeout('campaigns');

        $this->assertEquals('Sync timed out for campaigns', $e->getMessage());
    }

    /** @test */
    public function sync_exception_conflict(): void
    {
        $e = SyncException::conflict('subscriber', 123);

        $this->assertEquals('Sync conflict for subscriber #123: remote version differs', $e->getMessage());
    }
}
