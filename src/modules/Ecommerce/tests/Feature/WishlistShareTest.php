<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;
use Tests\TestCase;

class WishlistShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_get_share_url(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'ecommerce')
            ->get(route('ecommerce.wishlist.share-url'));

        $response->assertOk();
        $response->assertJsonStructure(['url']);

        $customer->refresh();
        $this->assertNotNull($customer->wishlist_share_token);
    }

    public function test_visitor_can_view_shared_wishlist_via_token(): void
    {
        $customer = Customer::factory()->create([
            'wishlist_share_token' => 'shared-token-abc123',
        ]);
        $product = Product::factory()->create([
            'status' => 'published',
            'name' => 'Producto Compartido XYZ',
        ]);
        Wishlist::query()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->get(route('shop.wishlist.shared', 'shared-token-abc123'));

        $response->assertOk();
        $response->assertSee('Producto Compartido XYZ');
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get(route('shop.wishlist.shared', 'invalid-token-xyz'));
        $response->assertNotFound();
    }
}
