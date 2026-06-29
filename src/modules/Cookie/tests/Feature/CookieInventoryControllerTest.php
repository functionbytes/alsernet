<?php

namespace Modules\Cookie\Tests\Feature;

use Modules\Cookie\Models\CookieInventory;
use Modules\Cookie\Tests\TestCase;

class CookieInventoryControllerTest extends TestCase
{
    // ───── Helpers ────────────────────────────────────────────────────────────

    private function inventoryData(array $overrides = []): array
    {
        return array_merge([
            'name' => '_ga',
            'provider' => 'Google Analytics',
            'category' => 'analytics',
            'purpose' => 'Distingue usuarios únicos',
            'duration' => '2 años',
        ], $overrides);
    }

    private function createInventoryItem(array $overrides = []): CookieInventory
    {
        return CookieInventory::create(array_merge([
            'name' => '_test_cookie',
            'provider' => 'Test Provider',
            'category' => 'essential',
            'purpose' => 'Testing purposes',
            'duration' => '1 año',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    // ───── index ──────────────────────────────────────────────────────────────

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('settings.cookie.inventory'))
            ->assertForbidden();
    }

    public function test_index_shows_inventory(): void
    {
        $this->createInventoryItem();

        $this->actingAs($this->createUser(['cookie.inventory.view']))
            ->get(route('settings.cookie.inventory'))
            ->assertOk()
            ->assertViewIs('cookie::settings.inventory');
    }

    // ───── store ──────────────────────────────────────────────────────────────

    public function test_store_requires_permission(): void
    {
        $this->actingAs($this->createUser())
            ->postJson(route('settings.cookie.inventory.store'), $this->inventoryData())
            ->assertForbidden();
    }

    public function test_store_creates_inventory_item(): void
    {
        $this->actingAs($this->createUser(['cookie.inventory.create']))
            ->postJson(route('settings.cookie.inventory.store'), $this->inventoryData())
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cookie_inventory', ['name' => '_ga']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->createUser(['cookie.inventory.create']))
            ->postJson(route('settings.cookie.inventory.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'provider', 'category', 'purpose', 'duration']);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->createInventoryItem(['name' => '_ga']);

        $this->actingAs($this->createUser(['cookie.inventory.create']))
            ->postJson(route('settings.cookie.inventory.store'), $this->inventoryData(['name' => '_ga']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_sets_is_active_false_when_unchecked(): void
    {
        $data = $this->inventoryData();
        unset($data['is_active']);

        $this->actingAs($this->createUser(['cookie.inventory.create']))
            ->postJson(route('settings.cookie.inventory.store'), $data)
            ->assertCreated();

        $this->assertDatabaseHas('cookie_inventory', ['name' => '_ga', 'is_active' => false]);
    }

    public function test_store_sets_is_active_true_when_checked(): void
    {
        $this->actingAs($this->createUser(['cookie.inventory.create']))
            ->postJson(route('settings.cookie.inventory.store'), $this->inventoryData(['is_active' => '1']))
            ->assertCreated();

        $this->assertDatabaseHas('cookie_inventory', ['name' => '_ga', 'is_active' => true]);
    }

    // ───── update ─────────────────────────────────────────────────────────────

    public function test_update_requires_permission(): void
    {
        $item = $this->createInventoryItem();

        $this->actingAs($this->createUser())
            ->putJson(route('settings.cookie.inventory.update', $item), $this->inventoryData())
            ->assertForbidden();
    }

    public function test_update_modifies_item(): void
    {
        $item = $this->createInventoryItem();

        $this->actingAs($this->createUser(['cookie.inventory.update']))
            ->putJson(route('settings.cookie.inventory.update', $item), $this->inventoryData([
                'name' => '_ga_updated',
                'provider' => 'Updated Provider',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cookie_inventory', [
            'id' => $item->id,
            'name' => '_ga_updated',
            'provider' => 'Updated Provider',
        ]);
    }

    public function test_update_sets_is_active_false_when_unchecked(): void
    {
        $item = $this->createInventoryItem(['is_active' => true]);

        $data = $this->inventoryData(['name' => $item->name]);
        unset($data['is_active']);

        $this->actingAs($this->createUser(['cookie.inventory.update']))
            ->putJson(route('settings.cookie.inventory.update', $item), $data)
            ->assertOk();

        $this->assertDatabaseHas('cookie_inventory', ['id' => $item->id, 'is_active' => false]);
    }

    // ───── destroy ────────────────────────────────────────────────────────────

    public function test_destroy_requires_permission(): void
    {
        $item = $this->createInventoryItem();

        $this->actingAs($this->createUser())
            ->deleteJson(route('settings.cookie.inventory.destroy', $item))
            ->assertForbidden();
    }

    public function test_destroy_deletes_item(): void
    {
        $item = $this->createInventoryItem();

        $this->actingAs($this->createUser(['cookie.inventory.delete']))
            ->deleteJson(route('settings.cookie.inventory.destroy', $item))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cookie_inventory', ['id' => $item->id]);
    }
}
