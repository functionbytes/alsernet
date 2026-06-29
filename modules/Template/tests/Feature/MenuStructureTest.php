<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Template\Models\Menu;
use Modules\Template\Models\MenuItem;
use Modules\Template\Tests\TestCase;

class MenuStructureTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Authentication guard — structure endpoint
    // -------------------------------------------------------------------------

    public function test_update_menu_structure_requires_auth(): void
    {
        $menu = Menu::factory()->create();

        $this->postJson(route('settings.menus.structure.update', $menu), [
            'items' => [],
        ])->assertUnauthorized();
    }

    public function test_store_menu_item_requires_auth(): void
    {
        $menu = Menu::factory()->create();

        $this->postJson(route('settings.menus.items.store', $menu), [
            'title' => 'Item',
            'type' => 'custom',
        ])->assertUnauthorized();
    }

    public function test_update_menu_item_requires_auth(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->putJson(route('settings.menus.items.update', [$menu, $item]), [
            'title' => 'Updated',
        ])->assertUnauthorized();
    }

    public function test_delete_menu_item_requires_auth(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->deleteJson(route('settings.menus.items.destroy', [$menu, $item]))
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Structure update (flat items)
    // -------------------------------------------------------------------------

    public function test_can_update_menu_structure_with_flat_items(): void
    {
        Gate::before(fn () => true);

        $menu = Menu::factory()->create();
        $item1 = MenuItem::factory()->create(['menu_id' => $menu->id, 'order' => 0]);
        $item2 = MenuItem::factory()->create(['menu_id' => $menu->id, 'order' => 1]);
        $item3 = MenuItem::factory()->create(['menu_id' => $menu->id, 'order' => 2]);

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.structure.update', $menu), [
                'items' => [
                    ['id' => $item2->id, 'order' => 0],
                    ['id' => $item3->id, 'order' => 1],
                    ['id' => $item1->id, 'order' => 2],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(2, $item1->fresh()->order);
        $this->assertEquals(0, $item2->fresh()->order);
        $this->assertEquals(1, $item3->fresh()->order);
    }

    public function test_can_update_menu_structure_with_nested_items(): void
    {
        Gate::before(fn () => true);

        $menu = Menu::factory()->create();
        $parent = MenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => null, 'order' => 0]);
        $child = MenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => null, 'order' => 1]);

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.structure.update', $menu), [
                'items' => [
                    ['id' => $parent->id, 'order' => 0, 'children' => [
                        ['id' => $child->id, 'order' => 0],
                    ]],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(0, $parent->fresh()->order);
        $this->assertEquals($parent->id, $child->fresh()->parent_id);
        $this->assertEquals(0, $child->fresh()->order);
    }

    // -------------------------------------------------------------------------
    // Menu items — store
    // -------------------------------------------------------------------------

    public function test_can_store_menu_item(): void
    {
        Gate::before(fn () => true);

        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'New Page Link',
                'url' => 'https://example.com/page',
                'type' => 'custom',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'New Page Link',
            'url' => 'https://example.com/page',
        ]);
    }

    // -------------------------------------------------------------------------
    // Menu items — update
    // -------------------------------------------------------------------------

    public function test_can_update_menu_item(): void
    {
        Gate::before(fn () => true);

        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Old Title',
            'url' => 'https://old.example.com',
        ]);

        $this->actingAs($this->user)
            ->putJson(route('settings.menus.items.update', [$menu, $item]), [
                'title' => 'New Title',
                'url' => 'https://new.example.com',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $item->refresh();
        $this->assertEquals('New Title', $item->title);
        $this->assertEquals('https://new.example.com', $item->url);
    }

    // -------------------------------------------------------------------------
    // Menu items — delete
    // -------------------------------------------------------------------------

    public function test_can_delete_menu_item(): void
    {
        Gate::before(fn () => true);

        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->actingAs($this->user)
            ->deleteJson(route('settings.menus.items.destroy', [$menu, $item]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }
}
