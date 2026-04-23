<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\ConversationStatus;
use Tests\TestCase;

class EmailInboundSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_postmark_valid_signature_passes(): void
    {
        $secret = 'postmark-secret';
        config(['helpdesk.email_inbound.postmark_webhook_secret' => $secret]);

        $body = '{"from":"test@example.com","subject":"Hello"}';
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->postJson(
            route('helpdesk.email-inbound', ['provider' => 'postmark']),
            json_decode($body, true),
            ['X-Postmark-Signature' => $signature]
        )->assertOk();
    }

    public function test_postmark_invalid_signature_fails(): void
    {
        config(['helpdesk.email_inbound.postmark_webhook_secret' => 'real-secret']);

        $this->postJson(
            route('helpdesk.email-inbound', ['provider' => 'postmark']),
            ['from' => 'test@example.com', 'subject' => 'Hello'],
            ['X-Postmark-Signature' => 'bad-signature']
        )->assertUnauthorized();
    }

    public function test_sendgrid_valid_secret_passes(): void
    {
        $secret = 'sendgrid-secret';
        config(['helpdesk.email_inbound.sendgrid_webhook_secret' => $secret]);

        $this->postJson(
            route('helpdesk.email-inbound', ['provider' => 'sendgrid']),
            ['from' => 'test@example.com', 'subject' => 'Hello'],
            ['X-Sendgrid-Signature' => $secret]
        )->assertOk();
    }

    public function test_sendgrid_missing_secret_skips_verification(): void
    {
        config(['helpdesk.email_inbound.sendgrid_webhook_secret' => '']);

        $this->postJson(
            route('helpdesk.email-inbound', ['provider' => 'sendgrid']),
            ['from' => 'test@example.com', 'subject' => 'Hello']
        )->assertOk();
    }
}
