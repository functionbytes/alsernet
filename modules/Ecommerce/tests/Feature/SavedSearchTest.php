<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\SavedSearch;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_saved_search(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.saved-searches.store'), [
                'name' => 'Camisetas baratas',
                'query' => 'camiseta',
                'filters' => ['max_price' => 50],
            ]);

        $this->assertDatabaseHas('ecommerce_saved_searches', [
            'customer_id' => $customer->id,
            'name' => 'Camisetas baratas',
        ]);
    }

    public function test_saved_search_requires_a_name(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.saved-searches.store'), [
                'query' => 'camiseta',
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('ecommerce_saved_searches', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_guest_cannot_create_saved_search(): void
    {
        $this->post(route('account.saved-searches.store'), [
            'name' => 'Test',
        ])->assertRedirect();
    }

    public function test_customer_can_toggle_search_off(): void
    {
        $customer = Customer::factory()->create();
        $search = SavedSearch::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Test',
            'is_active' => true,
            'filters' => [],
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.saved-searches.toggle', $search));

        $this->assertFalse($search->fresh()->is_active);
    }

    public function test_customer_can_toggle_search_on(): void
    {
        $customer = Customer::factory()->create();
        $search = SavedSearch::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Test',
            'is_active' => false,
            'filters' => [],
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->post(route('account.saved-searches.toggle', $search));

        $this->assertTrue($search->fresh()->is_active);
    }

    public function test_customer_can_delete_own_search(): void
    {
        $customer = Customer::factory()->create();
        $search = SavedSearch::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Test',
            'filters' => [],
        ]);

        $this->actingAs($customer, 'ecommerce')
            ->delete(route('account.saved-searches.destroy', $search));

        $this->assertDatabaseMissing('ecommerce_saved_searches', ['id' => $search->id]);
    }

    public function test_customer_cannot_delete_another_customers_search(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $search = SavedSearch::query()->create([
            'customer_id' => $owner->id,
            'name' => 'Test',
            'filters' => [],
        ]);

        $this->actingAs($other, 'ecommerce')
            ->delete(route('account.saved-searches.destroy', $search))
            ->assertForbidden();

        $this->assertDatabaseHas('ecommerce_saved_searches', ['id' => $search->id]);
    }

    public function test_customer_cannot_toggle_another_customers_search(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $search = SavedSearch::query()->create([
            'customer_id' => $owner->id,
            'name' => 'Test',
            'is_active' => true,
            'filters' => [],
        ]);

        $this->actingAs($other, 'ecommerce')
            ->post(route('account.saved-searches.toggle', $search))
            ->assertForbidden();

        $this->assertTrue($search->fresh()->is_active);
    }
}
