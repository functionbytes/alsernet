<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\ShippingRule;
use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    public function test_checkout_redirects_when_cart_is_empty(): void
    {
        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('checkout.index'));

        $response->assertRedirect(route('shop.index'));
    }

    public function test_checkout_validates_required_fields(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('checkout.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'address', 'city', 'country', 'payment_method']);
    }

    public function test_checkout_creates_order_successfully(): void
    {
        $product = Product::factory()->create([
            'status' => 'published',
            'price' => 100,
            'quantity' => 10,
            'with_storehouse_management' => true,
        ]);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 2]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('checkout.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'phone' => '555-0101',
                'address' => 'Calle 123',
                'city' => 'Bogota',
                'country' => 'Colombia',
                'zip_code' => '11001',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ecommerce_orders', ['payment_method' => 'cash']);
        $this->assertDatabaseHas('ecommerce_order_items', ['product_id' => $product->id, 'qty' => 2]);
        $this->assertDatabaseHas('ecommerce_order_histories', ['action' => 'create_order']);
        $this->assertDatabaseCount('ecommerce_carts', 0);
    }

    public function test_checkout_validates_stock(): void
    {
        $product = Product::factory()->create([
            'status' => 'published',
            'price' => 100,
            'quantity' => 1,
            'with_storehouse_management' => true,
            'allow_checkout_when_out_of_stock' => false,
        ]);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 5]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('checkout.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'address' => 'Calle 123',
                'city' => 'Bogota',
                'country' => 'Colombia',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('ecommerce_orders', 0);
    }

    public function test_checkout_calculates_shipping_and_tax(): void
    {
        $product = Product::factory()->create([
            'status' => 'published',
            'price' => 200,
            'quantity' => 10,
            'with_storehouse_management' => true,
        ]);

        $shipping = Shipping::factory()->create();
        ShippingRule::factory()->create([
            'shipping_id' => $shipping->id,
            'type' => 'based_on_price',
            'from' => 0,
            'to' => 500,
            'price' => 15,
        ]);

        $tax = Tax::factory()->create(['status' => 'published', 'percentage' => 10]);
        TaxRule::factory()->create(['tax_id' => $tax->id, 'percentage' => 10]);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('checkout.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'address' => 'Calle 123',
                'city' => 'Bogota',
                'country' => 'Colombia',
                'payment_method' => 'cash',
            ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(200, (float) $order->sub_total);
        $this->assertEquals(15, (float) $order->shipping_amount);
        $this->assertEquals(21.5, (float) $order->tax_amount);
        $this->assertEquals(236.5, (float) $order->total);
    }
}
