<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariation;
use Tests\TestCase;

class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_admin_can_create_variation_for_product(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->post(route('ecommerce.products.variations.store', $product), [
                'name' => 'Variación Talla M',
                'sku' => 'VAR-M-001',
                'price' => 50,
                'quantity' => 10,
                'is_default' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ecommerce_product_variations', [
            'configurable_product_id' => $product->id,
            'is_default' => true,
        ]);
    }

    public function test_create_variation_validates_required_fields(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->post(route('ecommerce.products.variations.store', $product), []);

        $response->assertSessionHasErrors(['name', 'price']);
    }

    public function test_admin_can_set_default_variation(): void
    {
        $product = Product::factory()->create();
        $variation1 = ProductVariation::query()->create([
            'configurable_product_id' => $product->id,
            'product_id' => Product::factory()->create()->id,
            'is_default' => true,
        ]);
        $variation2 = ProductVariation::query()->create([
            'configurable_product_id' => $product->id,
            'product_id' => Product::factory()->create()->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('ecommerce.products.variations.default', [$product, $variation2]));

        $response->assertRedirect();
        $this->assertDatabaseHas('ecommerce_product_variations', ['id' => $variation2->id, 'is_default' => true]);
        $this->assertDatabaseHas('ecommerce_product_variations', ['id' => $variation1->id, 'is_default' => false]);
    }

    public function test_admin_can_delete_variation(): void
    {
        $product = Product::factory()->create();
        $variation = ProductVariation::query()->create([
            'configurable_product_id' => $product->id,
            'product_id' => Product::factory()->create()->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('ecommerce.products.variations.destroy', [$product, $variation]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('ecommerce_product_variations', ['id' => $variation->id]);
    }

    public function test_unauthenticated_user_cannot_manage_variations(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('ecommerce.products.variations.store', $product), [
            'name' => 'Test',
            'price' => 10,
        ]);

        $response->assertRedirect();
        $response->assertRedirectContains('login');
    }
}
