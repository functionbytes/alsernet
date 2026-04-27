<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class ShopCouponTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
        Setting::set('ecommerce_payment.cod.status', '1');
    }

    private function addProductToCart(Product $product, int $qty = 1): void
    {
        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => $qty]);
    }

    private function postCheckout(array $extra = []): TestResponse
    {
        return $this->actingAs($this->customer, 'ecommerce')
            ->post(route('checkout.store'), array_merge([
                'name' => 'Juan Perez',
                'email' => 'juan@example.com',
                'address' => 'Calle 123',
                'city' => 'Bogota',
                'country' => 'Colombia',
                'payment_method' => 'cod',
            ], $extra));
    }

    public function test_checkout_applies_fixed_coupon(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);
        $coupon = Discount::factory()->create(['type' => 'fixed', 'value' => 20, 'code' => 'SAVE20']);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'SAVE20']);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(20.0, (float) $order->discount_amount);
        $this->assertEquals(80.0, (float) $order->total);
        $this->assertEquals('SAVE20', $order->coupon_code);
    }

    public function test_checkout_applies_percentage_coupon(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 200]);
        Discount::factory()->percentage()->create(['value' => 10, 'code' => 'PCT10']);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'PCT10']);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(20.0, (float) $order->discount_amount);
        $this->assertEquals(180.0, (float) $order->total);
    }

    public function test_checkout_ignores_invalid_coupon(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'DOESNOTEXIST']);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(0.0, (float) $order->discount_amount);
        $this->assertEquals(100.0, (float) $order->total);
    }

    public function test_checkout_ignores_expired_coupon(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);
        Discount::factory()->expired()->create(['value' => 20, 'code' => 'EXPIRED']);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'EXPIRED']);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(0.0, (float) $order->discount_amount);
    }

    public function test_checkout_ignores_inactive_coupon(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);
        Discount::factory()->inactive()->create(['value' => 20, 'code' => 'INACTIVE']);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'INACTIVE']);

        $order = Order::query()->first();
        $this->assertEquals(0.0, (float) $order->discount_amount);
    }

    public function test_checkout_increments_coupon_total_used(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);
        $coupon = Discount::factory()->create(['value' => 10, 'code' => 'USED', 'total_used' => 0]);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'USED']);

        $this->assertEquals(1, $coupon->fresh()->total_used);
    }

    public function test_checkout_does_not_apply_coupon_below_min_order(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 50]);
        Discount::factory()->create(['value' => 10, 'code' => 'MIN100', 'min_order_price' => 100]);

        $this->addProductToCart($product);
        $this->postCheckout(['coupon_code' => 'MIN100']);

        $order = Order::query()->first();
        $this->assertEquals(0.0, (float) $order->discount_amount);
    }

    public function test_checkout_without_coupon_has_no_discount(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);

        $this->addProductToCart($product);
        $this->postCheckout();

        $order = Order::query()->first();
        $this->assertEquals(0.0, (float) $order->discount_amount);
        $this->assertNull($order->coupon_code);
    }
}
