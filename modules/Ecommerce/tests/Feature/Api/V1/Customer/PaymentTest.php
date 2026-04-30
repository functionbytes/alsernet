<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Services\PaymentGatewayManager;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use DatabaseTransactions;

    private function mockManager(bool $hasCod = true, bool $codEnabled = true): void
    {
        $gateway = \Mockery::mock();
        $gateway->shouldReceive('isEnabled')->andReturn($codEnabled);
        $gateway->shouldReceive('getName')->andReturn('Pago contra entrega');
        $gateway->shouldReceive('getChannel')->andReturn('cod');
        $gateway->shouldReceive('getDescription')->andReturn('');
        $gateway->shouldReceive('getFee')->andReturn(0.0);

        $manager = \Mockery::mock(PaymentGatewayManager::class);
        $manager->shouldReceive('all')->andReturn($hasCod ? ['cod' => 'CodGateway'] : []);
        $manager->shouldReceive('get')->with('cod')->andReturn($gateway);
        $manager->shouldReceive('has')->with('cod')->andReturn($hasCod);
        $manager->shouldReceive('has')->with('nonexistent')->andReturn(false);

        $this->app->instance(PaymentGatewayManager::class, $manager);
    }

    // --- payment-methods ---

    public function test_guest_cannot_list_payment_methods(): void
    {
        $this->getJson('/api/v1/ecommerce/payment-methods')
            ->assertUnauthorized();
    }

    public function test_customer_can_list_enabled_payment_methods(): void
    {
        $this->mockManager(hasCod: true, codEnabled: true);

        Sanctum::actingAs(Customer::factory()->create(), ['*']);

        $response = $this->getJson('/api/v1/ecommerce/payment-methods');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_disabled_gateways_are_excluded_from_payment_methods(): void
    {
        $this->mockManager(hasCod: true, codEnabled: false);

        Sanctum::actingAs(Customer::factory()->create(), ['*']);

        $response = $this->getJson('/api/v1/ecommerce/payment-methods');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    // --- initiate payment ---

    public function test_guest_cannot_initiate_payment(): void
    {
        $order = Order::factory()->create();

        $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", ['channel' => 'cod'])
            ->assertUnauthorized();
    }

    public function test_customer_can_initiate_cod_payment_for_own_order(): void
    {
        $this->mockManager();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [
            'channel' => 'cod',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.channel', 'cod');
    }

    public function test_customer_cannot_initiate_payment_for_another_customers_order(): void
    {
        $this->mockManager();

        $order = Order::factory()->create(['payment_status' => 'pending']);
        $otherCustomer = Customer::factory()->create();

        Sanctum::actingAs($otherCustomer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [
            'channel' => 'cod',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_initiate_payment_for_already_paid_order(): void
    {
        $this->mockManager();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [
            'channel' => 'cod',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'ALREADY_PAID');
    }

    public function test_cannot_initiate_payment_with_unavailable_gateway(): void
    {
        $this->mockManager();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [
            'channel' => 'nonexistent',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'GATEWAY_NOT_FOUND');
    }

    public function test_initiate_payment_validates_missing_channel(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        Sanctum::actingAs($customer, ['*']);

        $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_initiate_payment_includes_return_url_in_response(): void
    {
        $this->mockManager();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order->id}/payments", [
            'channel' => 'cod',
            'return_url' => 'myapp://payment-result',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['paymentUrl', 'channel', 'gateway', 'instructions']]);
    }

    // --- payment status ---

    public function test_guest_cannot_check_payment_status(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/ecommerce/orders/{$order->id}/payments/1")
            ->assertUnauthorized();
    }

    public function test_customer_can_check_payment_status_for_own_order(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson("/api/v1/ecommerce/orders/{$order->id}/payments/1");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.orderId', $order->id)
            ->assertJsonPath('data.paymentStatus', 'pending');
    }

    public function test_customer_cannot_check_payment_status_of_another_customers_order(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);
        $otherCustomer = Customer::factory()->create();

        Sanctum::actingAs($otherCustomer, ['*']);

        $this->getJson("/api/v1/ecommerce/orders/{$order->id}/payments/1")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
