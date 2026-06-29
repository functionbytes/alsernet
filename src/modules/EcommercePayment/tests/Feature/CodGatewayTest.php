<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\CartService;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Services\CodGateway;
use Tests\TestCase;

class CodGatewayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::table('ecommerce_payments')->delete();

        parent::tearDown();
    }

    public function test_cod_gateway_channel_is_cod(): void
    {
        $gateway = new CodGateway;

        $this->assertSame('cod', $gateway->getChannel());
    }

    public function test_cod_gateway_fee_is_zero_when_type_is_none(): void
    {
        Setting::set('ecommerce_payment.cod.fee_type', 'none');

        $gateway = new CodGateway;

        $this->assertSame(0.0, $gateway->getFee(100));
    }

    public function test_cod_gateway_fee_fixed(): void
    {
        Setting::set('ecommerce_payment.cod.fee_type', 'fixed');
        Setting::set('ecommerce_payment.cod.fee_value', '5000');

        $gateway = new CodGateway;

        $this->assertSame(5000.0, $gateway->getFee(100000));
    }

    public function test_cod_gateway_fee_percentage(): void
    {
        Setting::set('ecommerce_payment.cod.fee_type', 'percentage');
        Setting::set('ecommerce_payment.cod.fee_value', '2');

        $gateway = new CodGateway;

        $this->assertSame(2000.0, $gateway->getFee(100000));
    }

    public function test_cod_gateway_is_disabled_by_default(): void
    {
        $gateway = new CodGateway;

        $this->assertFalse($gateway->isEnabled());
    }

    public function test_cod_gateway_make_payment_creates_payment_record(): void
    {
        $this->mock(CartService::class, function ($mock) {
            $mock->shouldReceive('clearCart')->once();
        });

        $order = Order::factory()->create();

        $gateway = new CodGateway;
        $gateway->makePayment($order, []);

        $this->assertDatabaseHas('ecommerce_payments', [
            'order_id' => $order->id,
            'payment_channel' => 'cod',
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_cod_gateway_make_payment_redirects_to_confirmation(): void
    {
        $this->mock(CartService::class, function ($mock) {
            $mock->shouldReceive('clearCart')->once();
        });

        $order = Order::factory()->create();

        $gateway = new CodGateway;
        $response = $gateway->makePayment($order, []);

        $this->assertTrue($response->isRedirect());
    }
}
