<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Services\WompiGateway;
use Tests\TestCase;

class WidgetErrorTest extends TestCase
{
    public function test_make_payment_returns_error_when_not_enabled(): void
    {
        Setting::set('ecommerce_payment.wompi.status', '0');

        $order = new Order([
            'id' => 1,
            'code' => 'ORD-TEST',
            'token' => 'TOK-TEST',
            'total' => 100,
        ]);
        $order->setRelation('customer', new Customer(['name' => 'Test']));

        $gateway = new WompiGateway;
        $response = $gateway->makePayment($order, [
            'email' => 'test@test.com',
            'name' => 'Test',
        ]);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('No se puede procesar el pago', $response->getContent());
    }
}
