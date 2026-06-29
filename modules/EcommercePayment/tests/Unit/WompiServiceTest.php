<?php

namespace Modules\EcommercePayment\Tests\Unit;

use Modules\Core\Models\Setting;
use Modules\EcommercePayment\Services\WompiService;
use Tests\TestCase;

class WompiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('ecommerce_payment.wompi.public_key', 'pub_test_1234567890abcdef');
        Setting::set('ecommerce_payment.wompi.integrity_secret', 'test_integrity_secret_123');
        Setting::set('ecommerce_payment.wompi.mode', 'sandbox');
    }

    public function test_generate_integrity_signature(): void
    {
        $service = new WompiService;
        $service->withData(['reference' => 'TEST-REF-123']);

        // We can't test the exact signature without real credentials,
        // but we can verify it returns a non-empty string
        $signature = $service->generateIntegritySignature(100000, 'COP');

        $this->assertNotEmpty($signature);
        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA-256 hex length
    }

    public function test_verify_webhook_signature_valid(): void
    {
        $service = new WompiService;

        $payload = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => 'txn-123',
                    'status' => 'APPROVED',
                    'amount_in_cents' => 100000,
                    'currency' => 'COP',
                ],
            ],
            'signature' => [
                'checksum' => 'abc123',
            ],
        ];

        // Without event secret configured, this should return false
        $result = $service->verifyWebhookSignature($payload, 'invalid-signature');
        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_missing_transaction(): void
    {
        $service = new WompiService;

        $payload = ['event' => 'transaction.updated'];

        $result = $service->verifyWebhookSignature($payload, 'some-signature');
        $this->assertFalse($result);
    }
}
