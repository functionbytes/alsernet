<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Tests\TestCase;

class RecentlyViewedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_recently_viewed(): void
    {
        $this->getJson('/api/v1/me/recently-viewed')
            ->assertUnauthorized();
    }

    public function test_customer_gets_empty_list_when_no_recently_viewed(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/me/recently-viewed');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_can_see_recently_viewed_products(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $customer->recentlyViewed()->attach($product->id, [
            'session_id' => 'test-session',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/me/recently-viewed');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_recently_viewed_respects_limit_parameter(): void
    {
        $customer = Customer::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $i => $product) {
            $customer->recentlyViewed()->attach($product->id, [
                'session_id' => 'session-'.$i,
            ]);
        }

        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/me/recently-viewed?limit=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_customer_can_track_product_view(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $this->postJson("/api/v1/me/recently-viewed/{$product->id}")
            ->assertOk();

        $this->assertDatabaseHas('ecommerce_customer_recently_viewed_products', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_tracking_same_product_twice_does_not_duplicate(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $this->postJson("/api/v1/me/recently-viewed/{$product->id}");
        $this->postJson("/api/v1/me/recently-viewed/{$product->id}");

        $this->assertDatabaseCount('ecommerce_customer_recently_viewed_products', 1);
    }

    public function test_track_requires_auth(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/v1/me/recently-viewed/{$product->id}")
            ->assertUnauthorized();
    }
}
