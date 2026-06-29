<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class ShopCartTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    public function test_authenticated_customer_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 2]);

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('ecommerce_carts', [
            'product_id' => $product->id,
            'customer_id' => $this->customer->id,
            'qty' => 2,
        ]);
    }

    public function test_authenticated_customer_can_view_cart(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'price' => 100, 'name' => 'Test Product']);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 3]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('Test Product');
    }

    public function test_authenticated_customer_can_update_cart_quantity(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 1]);

        $cart = Cart::query()->first();

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->put(route('cart.update', $cart->id), ['qty' => 5]);

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('ecommerce_carts', ['id' => $cart->id, 'qty' => 5]);
    }

    public function test_authenticated_customer_can_remove_cart_item(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 1]);

        $cart = Cart::query()->first();

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->delete(route('cart.remove', $cart->id));

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseMissing('ecommerce_carts', ['id' => $cart->id]);
    }

    public function test_authenticated_customer_can_clear_cart(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.add', $product), ['qty' => 2]);

        $response = $this->actingAs($this->customer, 'ecommerce')
            ->post(route('cart.clear'));

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('ecommerce_carts', 0);
    }
}
