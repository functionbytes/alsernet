<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Mail\ProductBackInStockMail;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductRestockAlert;
use Tests\TestCase;

class RestockAlertTest extends TestCase
{
    use RefreshDatabase;

    private function outOfStockProduct(): Product
    {
        return Product::factory()->create([
            'status' => 'published',
            'quantity' => 0,
            'with_storehouse_management' => true,
            'allow_checkout_when_out_of_stock' => false,
        ]);
    }

    public function test_visitor_can_request_restock_alert(): void
    {
        $product = $this->outOfStockProduct();

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('shop.products.restock-alert', $product), [
                'email' => 'visitor@example.com',
            ]);

        $this->assertDatabaseHas('ecommerce_product_restock_alerts', [
            'product_id' => $product->id,
            'email' => 'visitor@example.com',
        ]);
    }

    public function test_request_validates_email(): void
    {
        $product = $this->outOfStockProduct();

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('shop.products.restock-alert', $product), [
                'email' => 'not-an-email',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_request_for_in_stock_product_returns_warning(): void
    {
        $product = Product::factory()->create([
            'status' => 'published',
            'quantity' => 10,
            'with_storehouse_management' => true,
        ]);

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('shop.products.restock-alert', $product), [
                'email' => 'visitor@example.com',
            ]);

        $this->assertDatabaseMissing('ecommerce_product_restock_alerts', [
            'product_id' => $product->id,
            'email' => 'visitor@example.com',
        ]);
    }

    public function test_duplicate_request_is_idempotent(): void
    {
        $product = $this->outOfStockProduct();

        $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('shop.products.restock-alert', $product), ['email' => 'a@b.com']);

        $this->withoutMiddleware([ThrottleRequests::class])
            ->post(route('shop.products.restock-alert', $product), ['email' => 'a@b.com']);

        $this->assertEquals(1, ProductRestockAlert::query()
            ->where('product_id', $product->id)
            ->where('email', 'a@b.com')
            ->count());
    }

    public function test_command_sends_emails_when_product_has_stock(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'status' => 'published',
            'quantity' => 5,
            'with_storehouse_management' => true,
        ]);

        ProductRestockAlert::factory()->create([
            'product_id' => $product->id,
            'email' => 'subscriber@example.com',
            'notified_at' => null,
        ]);

        $this->artisan('ecommerce:send-restock-alerts')->assertSuccessful();

        Mail::assertQueued(ProductBackInStockMail::class);
        $this->assertNotNull(ProductRestockAlert::query()->first()->notified_at);
    }

    public function test_command_does_not_send_when_product_still_out_of_stock(): void
    {
        Mail::fake();

        $product = $this->outOfStockProduct();

        ProductRestockAlert::factory()->create([
            'product_id' => $product->id,
            'email' => 'subscriber@example.com',
            'notified_at' => null,
        ]);

        $this->artisan('ecommerce:send-restock-alerts')->assertSuccessful();

        Mail::assertNotQueued(ProductBackInStockMail::class);
    }
}
