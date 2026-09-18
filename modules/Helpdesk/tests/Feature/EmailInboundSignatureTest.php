<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\ConversationStatus;
use Tests\TestCase;

class EmailInboundSignatureTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

        // Use Postmark PascalCase field names as Postmark actually sends them.
        // The signature is computed over the exact JSON string that postJson will transmit.
        $payload = ['From' => 'test@example.com', 'Subject' => 'Hello', 'TextBody' => 'Test body'];
        $jsonBody = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $jsonBody, $secret, true));

        $this->postJson(
            route('helpdesk.webhooks.email-inbound', ['provider' => 'postmark']),
            $payload,
            ['X-Postmark-Signature' => $signature]
        )->assertOk();
    }

    public function test_postmark_invalid_signature_fails(): void
    {
        config(['helpdesk.email_inbound.postmark_webhook_secret' => 'real-secret']);

        $this->postJson(
            route('helpdesk.webhooks.email-inbound', ['provider' => 'postmark']),
            ['from' => 'test@example.com', 'subject' => 'Hello'],
            ['X-Postmark-Signature' => 'bad-signature']
        )->assertUnauthorized();
    }

    public function test_sendgrid_valid_secret_passes(): void
    {
        $secret = 'sendgrid-secret';
        config(['helpdesk.email_inbound.sendgrid_webhook_secret' => $secret]);

        $this->postJson(
            route('helpdesk.webhooks.email-inbound', ['provider' => 'sendgrid']),
            ['from' => 'test@example.com', 'subject' => 'Hello'],
            ['X-Sendgrid-Signature' => $secret]
        )->assertOk();
    }

    public function test_sendgrid_missing_secret_is_rejected_outside_local(): void
    {
        config(['helpdesk.email_inbound.sendgrid_webhook_secret' => '']);

        // Fail-closed: sin secreto configurado (entorno testing, no local) se rechaza.
        $this->postJson(
            route('helpdesk.webhooks.email-inbound', ['provider' => 'sendgrid']),
            ['from' => 'test@example.com', 'subject' => 'Hello']
        )->assertUnauthorized();
    }
}
