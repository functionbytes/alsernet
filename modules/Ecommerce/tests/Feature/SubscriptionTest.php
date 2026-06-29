<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Subscription;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_subscribe_to_subscription_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'status' => 'published',
            'is_subscription' => true,
            'subscription_interval' => 'monthly',
            'subscription_discount_percent' => 10,
            'price' => 100,
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('shop.product.subscribe', $product), ['qty' => 1]);

        $this->assertDatabaseHas('ecommerce_subscriptions', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 'active',
        ]);
    }

    public function test_subscribe_applies_subscription_discount(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'status' => 'published',
            'is_subscription' => true,
            'subscription_interval' => 'monthly',
            'subscription_discount_percent' => 10,
            'price' => 100,
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('shop.product.subscribe', $product), ['qty' => 1]);

        $this->assertDatabaseHas('ecommerce_subscriptions', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'price' => '90.00',
        ]);
    }

    public function test_cannot_subscribe_to_non_subscription_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'status' => 'published',
            'is_subscription' => false,
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('shop.product.subscribe', $product));

        $this->assertDatabaseMissing('ecommerce_subscriptions', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_guest_cannot_subscribe(): void
    {
        $product = Product::factory()->create([
            'status' => 'published',
            'is_subscription' => true,
        ]);

        $this->post(route('shop.product.subscribe', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('ecommerce_subscriptions', [
            'product_id' => $product->id,
        ]);
    }

    public function test_customer_can_pause_subscription(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $sub = Subscription::query()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 1,
            'interval' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'next_billing_at' => now()->addMonth(),
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.subscriptions.pause', $sub));

        $this->assertEquals('paused', $sub->fresh()->status);
    }

    public function test_customer_can_resume_paused_subscription(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $sub = Subscription::query()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 1,
            'interval' => 'monthly',
            'price' => 100,
            'status' => 'paused',
            'next_billing_at' => now()->addMonth(),
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.subscriptions.resume', $sub));

        $this->assertEquals('active', $sub->fresh()->status);
    }

    public function test_customer_can_cancel_subscription(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $sub = Subscription::query()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 1,
            'interval' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'next_billing_at' => now()->addMonth(),
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.subscriptions.cancel', $sub));

        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    public function test_customer_cannot_pause_another_customers_subscription(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $product = Product::factory()->create();
        $sub = Subscription::query()->create([
            'customer_id' => $owner->id,
            'product_id' => $product->id,
            'qty' => 1,
            'interval' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'next_billing_at' => now()->addMonth(),
        ]);

        $this->actingAs($other, 'ecommerce')
            ->post(route('account.subscriptions.pause', $sub))
            ->assertForbidden();

        $this->assertEquals('active', $sub->fresh()->status);
    }

    public function test_customer_cannot_cancel_another_customers_subscription(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $product = Product::factory()->create();
        $sub = Subscription::query()->create([
            'customer_id' => $owner->id,
            'product_id' => $product->id,
            'qty' => 1,
            'interval' => 'monthly',
            'price' => 100,
            'status' => 'active',
            'next_billing_at' => now()->addMonth(),
        ]);

        $this->actingAs($other, 'ecommerce')
            ->post(route('account.subscriptions.cancel', $sub))
            ->assertForbidden();
    }
}
