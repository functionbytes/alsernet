<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_apply_valid_fixed_coupon(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'status' => 'published']);
        Cart::factory()->create(['customer_id' => $customer->id, 'product_id' => $product->id, 'qty' => 1]);
        $discount = Discount::factory()->create(['type' => DiscountType::FIXED, 'value' => 10.00]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => $discount->code]);

        $response->assertOk()
            ->assertJsonPath('data.discountAmount', 10)
            ->assertJsonPath('data.couponCode', $discount->code);
    }

    public function test_customer_can_apply_percentage_coupon(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'status' => 'published']);
        Cart::factory()->create(['customer_id' => $customer->id, 'product_id' => $product->id, 'qty' => 1]);
        $discount = Discount::factory()->percentage()->create(['value' => 10]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => $discount->code]);

        $response->assertOk()
            ->assertJsonPath('data.discountAmount', 10);
    }

    public function test_applying_expired_coupon_returns_422(): void
    {
        $customer = Customer::factory()->create();
        $discount = Discount::factory()->expired()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => $discount->code]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'COUPON_EXPIRED');
    }

    public function test_applying_inactive_coupon_returns_422(): void
    {
        $customer = Customer::factory()->create();
        $discount = Discount::factory()->inactive()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => $discount->code]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'COUPON_NOT_FOUND');
    }

    public function test_applying_coupon_below_min_order_fails(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'status' => 'published']);
        Cart::factory()->create(['customer_id' => $customer->id, 'product_id' => $product->id, 'qty' => 1]);
        $discount = Discount::factory()->create(['min_order_price' => 500.00, 'value' => 10.00]);
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => $discount->code]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'COUPON_MIN_ORDER');
    }

    public function test_applying_nonexistent_coupon_returns_422(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => 'FAKE123']);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'COUPON_NOT_FOUND');
    }

    public function test_customer_can_remove_coupon(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->deleteJson('/api/v1/ecommerce/cart/coupons');

        $response->assertOk()
            ->assertJsonPath('data.couponCode', null);
    }

    public function test_apply_coupon_requires_auth(): void
    {
        $this->postJson('/api/v1/ecommerce/cart/coupons', ['code' => 'TEST'])
            ->assertUnauthorized();
    }
}
