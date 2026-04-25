<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Services\WompiGateway;
use Tests\TestCase;

class WompiGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('ecommerce_payment.wompi.status', '1');
        Setting::set('ecommerce_payment.wompi.public_key', 'pub_test_1234567890abcdef');
        Setting::set('ecommerce_payment.wompi.private_key', 'prv_test_1234567890abcdef');
        Setting::set('ecommerce_payment.wompi.integrity_secret', 'test_integrity_secret');
        Setting::set('ecommerce_payment.wompi.event_secret', 'test_event_secret');
        Setting::set('ecommerce_payment.wompi.mode', 'sandbox');
    }

    protected function tearDown(): void
    {
        DB::table('ecommerce_payments')->delete();

        parent::tearDown();
    }

    public function test_create_pending_payment(): void
    {
        $gateway = new WompiGateway;

        $chargeId = $gateway->createPendingPayment([
            'amount' => 100.00,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'order_id' => null,
            'payment_type' => 'wompi_checkout',
        ]);

        $this->assertNotEmpty($chargeId);
        $this->assertStringStartsWith('WP', $chargeId);

        $this->assertDatabaseHas('ecommerce_payments', [
            'charge_id' => $chargeId,
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_is_enabled_returns_true_when_configured(): void
    {
        $gateway = new WompiGateway;
        $this->assertTrue($gateway->isEnabled());
    }

    public function test_handle_webhook_returns_error_without_signature(): void
    {
        $gateway = new WompiGateway;

        $request = new Request;
        $request->merge([
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'txn-123']],
        ]);

        $result = $gateway->handleWebhook($request);

        $this->assertFalse($result['success']);
        $this->assertEquals('Missing signature', $result['message']);
    }
}
