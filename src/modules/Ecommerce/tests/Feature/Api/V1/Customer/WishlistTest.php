<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_add_product_to_wishlist(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/ecommerce/wishlist/'.$product->id);

        $response->assertCreated();
        $this->assertDatabaseHas('ecommerce_wish_lists', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_adding_same_product_twice_is_idempotent(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $this->postJson('/api/v1/ecommerce/wishlist/'.$product->id);
        $this->postJson('/api/v1/ecommerce/wishlist/'.$product->id);

        $this->assertSame(1, Wishlist::query()->where('customer_id', $customer->id)->where('product_id', $product->id)->count());
    }

    public function test_customer_can_list_wishlist(): void
    {
        $customer = Customer::factory()->create();
        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            Wishlist::query()->create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
            ]);
        }
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/ecommerce/wishlist');

        $response->assertOk()->assertJsonPath('meta.total', 3);
    }
}
