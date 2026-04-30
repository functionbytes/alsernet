<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    // --- public listing ---

    public function test_anyone_can_list_approved_reviews_for_a_product(): void
    {
        $product = Product::factory()->create();
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'status' => 'approved',
        ]);
        Review::factory()->create([
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/ecommerce/products/{$product->slug}/reviews");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_reviews_listing_returns_empty_for_product_with_no_approved_reviews(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/ecommerce/products/{$product->slug}/reviews");

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    // --- create review ---

    public function test_guest_cannot_create_review(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/v1/ecommerce/products/{$product->slug}/reviews", [
            'star' => 5,
            'comment' => 'Excelente producto de muy buena calidad.',
        ])->assertUnauthorized();
    }

    public function test_verified_buyer_can_create_review(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/products/{$product->slug}/reviews", [
            'star' => 5,
            'comment' => 'Excelente producto de muy buena calidad.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.star', 5);

        $this->assertDatabaseHas('ecommerce_reviews', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'is_verified_buyer' => true,
        ]);
    }

    public function test_non_buyer_cannot_create_review(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson("/api/v1/ecommerce/products/{$product->slug}/reviews", [
            'star' => 4,
            'comment' => 'Buen producto en general.',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'NOT_VERIFIED_BUYER');
    }

    public function test_create_review_validates_missing_star(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($customer, ['*']);

        $this->postJson("/api/v1/ecommerce/products/{$product->slug}/reviews", [
            'comment' => 'Buen producto en general.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['star']);
    }

    public function test_create_review_validates_star_range(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($customer, ['*']);

        $this->postJson("/api/v1/ecommerce/products/{$product->slug}/reviews", [
            'star' => 6,
            'comment' => 'Buen producto en general.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['star']);
    }

    // --- update review (7-day lock) ---

    public function test_customer_can_edit_own_recent_review(): void
    {
        $customer = Customer::factory()->create();
        $review = Review::factory()->create([
            'customer_id' => $customer->id,
            'star' => 3,
            'comment' => 'Comentario original del producto.',
            'created_at' => now()->subDays(2),
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->putJson("/api/v1/ecommerce/reviews/{$review->id}", [
            'star' => 4,
            'comment' => 'Comentario actualizado del producto.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.star', 4);
    }

    public function test_review_is_locked_after_7_days(): void
    {
        $customer = Customer::factory()->create();
        $review = Review::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(8),
        ]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->putJson("/api/v1/ecommerce/reviews/{$review->id}", [
            'comment' => 'Intento de edicion tardía del comentario.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'REVIEW_LOCKED');
    }

    public function test_customer_cannot_edit_another_customers_review(): void
    {
        $owner = Customer::factory()->create();
        $review = Review::factory()->create([
            'customer_id' => $owner->id,
            'created_at' => now()->subDay(),
        ]);

        $otherCustomer = Customer::factory()->create();
        Sanctum::actingAs($otherCustomer, ['*']);

        $this->putJson("/api/v1/ecommerce/reviews/{$review->id}", [
            'comment' => 'Intento de edicion no autorizada.',
        ])->assertForbidden();
    }

    // --- delete review ---

    public function test_customer_can_delete_own_review(): void
    {
        $customer = Customer::factory()->create();
        $review = Review::factory()->create(['customer_id' => $customer->id]);

        Sanctum::actingAs($customer, ['*']);

        $response = $this->deleteJson("/api/v1/ecommerce/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('ecommerce_reviews', ['id' => $review->id]);
    }

    public function test_customer_cannot_delete_another_customers_review(): void
    {
        $owner = Customer::factory()->create();
        $review = Review::factory()->create(['customer_id' => $owner->id]);

        $otherCustomer = Customer::factory()->create();
        Sanctum::actingAs($otherCustomer, ['*']);

        $this->deleteJson("/api/v1/ecommerce/reviews/{$review->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('ecommerce_reviews', ['id' => $review->id]);
    }

    public function test_guest_cannot_delete_review(): void
    {
        $review = Review::factory()->create();

        $this->deleteJson("/api/v1/ecommerce/reviews/{$review->id}")
            ->assertUnauthorized();
    }
}
