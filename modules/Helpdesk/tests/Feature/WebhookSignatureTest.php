<?php

namespace Modules\Helpdesk\Tests\Feature;

use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure all WhatsApp config keys have non-null string defaults so the
        // service constructor (which assigns typed string properties) does not
        // throw a TypeError in the test environment where the config file may
        // not be loaded.
        config([
            'helpdesk.integrations.whatsapp.api_url' => 'https://graph.facebook.com/v19.0',
            'helpdesk.integrations.whatsapp.phone_number_id' => '',
            'helpdesk.integrations.whatsapp.access_token' => '',
            'helpdesk.integrations.whatsapp.app_secret' => '',
            'helpdesk.integrations.whatsapp.verify_token' => '',
            'helpdesk.integrations.whatsapp.enabled' => false,
        ]);
    }

    /** Build a fresh service instance (bypasses the singleton cache). */
    private function makeService(): WhatsAppBusinessService
    {
        return new WhatsAppBusinessService;
    }

    public function test_valid_whatsapp_signature_passes(): void
    {
        $secret = 'test-secret';
        config(['helpdesk.integrations.whatsapp.app_secret' => $secret]);

        $body = '{"test":"payload"}';
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

        $this->assertTrue($this->makeService()->verifySignature($body, $signature));
    }

    public function test_invalid_whatsapp_signature_fails(): void
    {
        config(['helpdesk.integrations.whatsapp.app_secret' => 'real-secret']);

        $this->assertFalse(
            $this->makeService()->verifySignature('{"test":"payload"}', 'sha256=badsignature')
        );
    }

    public function test_signature_skipped_when_no_secret_configured(): void
    {
        config(['helpdesk.integrations.whatsapp.app_secret' => '']);

        $this->assertTrue($this->makeService()->verifySignature('any body', 'sha256=anything'));
    }

    public function test_different_body_produces_different_signature(): void
    {
        $secret = 'shared-secret';
        config(['helpdesk.integrations.whatsapp.app_secret' => $secret]);

        $body = '{"data":"original"}';
        $tamperedBody = '{"data":"tampered"}';
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

        $this->assertFalse($this->makeService()->verifySignature($tamperedBody, $signature));
    }

    public function test_empty_body_with_valid_signature_passes(): void
    {
        $secret = 'some-secret';
        config(['helpdesk.integrations.whatsapp.app_secret' => $secret]);

        $body = '';
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

        $this->assertTrue($this->makeService()->verifySignature($body, $signature));
    }
}
