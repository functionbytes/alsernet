<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_order_status_creates_activity_log(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $order->update(['status' => 'completed']);

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('log_name', 'ecommerce_order')
                ->exists()
        );
    }

    public function test_updating_product_price_creates_activity_log(): void
    {
        $product = Product::factory()->create(['price' => 100]);

        $product->update(['price' => 150]);

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', Product::class)
                ->where('subject_id', $product->id)
                ->where('log_name', 'ecommerce_product')
                ->exists()
        );
    }

    public function test_updating_customer_creates_activity_log(): void
    {
        $customer = Customer::factory()->create(['name' => 'Original']);

        $customer->update(['name' => 'Modificado']);

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', Customer::class)
                ->where('subject_id', $customer->id)
                ->where('log_name', 'ecommerce_customer')
                ->exists()
        );
    }

    public function test_updating_discount_creates_activity_log(): void
    {
        $discount = Discount::factory()->create(['value' => 10]);

        $discount->update(['value' => 25]);

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', Discount::class)
                ->where('subject_id', $discount->id)
                ->where('log_name', 'ecommerce_discount')
                ->exists()
        );
    }

    public function test_activity_log_records_old_and_new_values(): void
    {
        $product = Product::factory()->create(['price' => 100, 'name' => 'Original']);

        $product->update(['price' => 200]);

        $activity = Activity::query()
            ->where('subject_type', Product::class)
            ->where('subject_id', $product->id)
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals(200, $activity->properties['attributes']['price'] ?? null);
        $this->assertEquals(100, $activity->properties['old']['price'] ?? null);
    }
}
