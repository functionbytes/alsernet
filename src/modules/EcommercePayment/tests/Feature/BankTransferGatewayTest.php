<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\CartService;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Services\BankTransferGateway;
use Tests\TestCase;

class BankTransferGatewayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::table('ecommerce_payments')->delete();

        parent::tearDown();
    }

    public function test_bank_transfer_channel_is_bank_transfer(): void
    {
        $gateway = new BankTransferGateway;

        $this->assertSame('bank_transfer', $gateway->getChannel());
    }

    public function test_bank_transfer_fee_is_always_zero(): void
    {
        $gateway = new BankTransferGateway;

        $this->assertSame(0.0, $gateway->getFee(999999));
    }

    public function test_bank_transfer_is_disabled_by_default(): void
    {
        $gateway = new BankTransferGateway;

        $this->assertFalse($gateway->isEnabled());
    }

    public function test_bank_transfer_make_payment_creates_payment_record(): void
    {
        Mail::fake();

        $this->mock(CartService::class, function ($mock) {
            $mock->shouldReceive('clearCart')->once();
        });

        $order = Order::factory()->create();

        $gateway = new BankTransferGateway;
        $gateway->makePayment($order, []);

        $this->assertDatabaseHas('ecommerce_payments', [
            'order_id' => $order->id,
            'payment_channel' => 'bank_transfer',
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_bank_transfer_make_payment_redirects(): void
    {
        Mail::fake();

        $this->mock(CartService::class, function ($mock) {
            $mock->shouldReceive('clearCart')->once();
        });

        $order = Order::factory()->create();

        $gateway = new BankTransferGateway;
        $response = $gateway->makePayment($order, []);

        $this->assertTrue($response->isRedirect());
    }

    public function test_bank_transfer_instructions_page_requires_valid_signature(): void
    {
        $order = Order::factory()->create();

        $this->get(route('payment.bank-transfer.instructions', $order))
            ->assertForbidden();
    }
}
