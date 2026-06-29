<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Ecommerce\Models\Product;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Models\AssistedCart;
use Tests\TestCase;

class AssistedCartTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['helpdesk.conversations.view', 'helpdesk.conversations.update']);
    }

    public function test_agent_can_add_product_and_totals_are_calculated(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'sale_price' => null]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart.items_count', 1)
            ->assertJsonPath('cart.units_count', 2)
            ->assertJsonPath('cart.subtotal', 200)
            ->assertJsonPath('cart.total', 200);

        $this->assertDatabaseHas('helpdesk_assisted_carts', [
            'customer_id' => $customer->id,
            'status' => AssistedCart::STATUS_BUILDING,
        ], 'helpdesk');
    }

    public function test_adding_same_product_twice_increments_quantity(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $response = $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id]);

        $response->assertOk()
            ->assertJsonPath('cart.items_count', 1)
            ->assertJsonPath('cart.units_count', 2);
    }

    public function test_agent_can_update_and_remove_cart_items(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 30, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $item = AssistedCart::query()->forCustomer($customer->id)->building()->first()->items()->first();

        $this->actingAs($this->agent)
            ->patchJson(route('manager.helpdesk.customers.cart.items.update', [$customer, $item->id]), ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('cart.units_count', 5);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.customers.cart.items.destroy', [$customer, $item->id]))
            ->assertOk()
            ->assertJsonPath('cart.items_count', 0);
    }

    public function test_agent_can_list_customer_carts(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10, 'sale_price' => null]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.customers.carts.index', $customer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.active', 1)
            ->assertJsonCount(1, 'carts');
    }

    public function test_user_without_permission_cannot_modify_cart(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10, 'sale_price' => null]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => $product->id])
            ->assertForbidden();
    }

    public function test_add_item_validates_product_exists(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.customers.cart.items.store', $customer), ['product_id' => 999999])
            ->assertUnprocessable();
    }
}
