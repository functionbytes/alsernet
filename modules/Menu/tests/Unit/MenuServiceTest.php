<?php

namespace Modules\Menu\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Services\MenuService;
use Tests\TestCase;

class MenuServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MenuService $menuService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->menuService = app(MenuService::class);
    }

    /** @test */
    public function it_can_create_a_menu()
    {
        $data = [
            'name' => 'Test Menu',
            'slug' => 'test-menu',
            'location' => 'header',
            'status' => true,
        ];

        $menu = $this->menuService->createMenu($data);

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals('Test Menu', $menu->name);
        $this->assertEquals('test-menu', $menu->slug);
        $this->assertEquals('header', $menu->location);
        $this->assertTrue($menu->status);
    }

    /** @test */
    public function it_can_update_a_menu()
    {
        $menu = Menu::factory()->create([
            'name' => 'Original Name',
            'location' => 'header',
        ]);

        $updatedMenu = $this->menuService->updateMenu($menu, [
            'name' => 'Updated Name',
            'location' => 'footer',
        ]);

        $this->assertEquals('Updated Name', $updatedMenu->name);
        $this->assertEquals('footer', $updatedMenu->location);
    }

    /** @test */
    public function it_can_delete_a_menu()
    {
        $menu = Menu::factory()->create();

        $this->menuService->deleteMenu($menu);

        $this->assertSoftDeleted('menus', ['id' => $menu->id]);
    }

    /** @test */
    public function it_can_add_a_menu_item()
    {
        $menu = Menu::factory()->create();

        $item = $this->menuService->addMenuItem($menu, [
            'title' => 'Home',
            'url' => '/',
            'type' => 'custom',
        ]);

        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertEquals('Home', $item->title);
        $this->assertEquals('/', $item->url);
        $this->assertEquals($menu->id, $item->menu_id);
    }

    /** @test */
    public function it_can_update_a_menu_item()
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Original Title',
        ]);

        $updatedItem = $this->menuService->updateMenuItem($item, [
            'title' => 'Updated Title',
        ]);

        $this->assertEquals('Updated Title', $updatedItem->title);
    }

    /** @test */
    public function it_can_delete_a_menu_item()
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->menuService->deleteMenuItem($item);

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }

    /** @test */
    public function it_can_render_a_menu()
    {
        $menu = Menu::factory()->create([
            'location' => 'header',
            'status' => true,
        ]);

        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Home',
            'url' => '/',
            'order' => 0,
        ]);

        $html = $this->menuService->renderMenu('header');

        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('<ul', $html);
    }

    /** @test */
    public function it_renders_nested_menu_items()
    {
        $menu = Menu::factory()->create([
            'location' => 'header',
            'status' => true,
        ]);

        $parent = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Parent',
            'order' => 0,
        ]);

        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child',
            'order' => 0,
        ]);

        $html = $this->menuService->renderMenu('header');

        $this->assertStringContainsString('Parent', $html);
        $this->assertStringContainsString('Child', $html);
    }

    /** @test */
    public function it_respects_max_depth_configuration()
    {
        config(['menu.max_depth' => 2]);

        $menu = Menu::factory()->create([
            'location' => 'header',
            'status' => true,
        ]);

        $level1 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Level 1',
        ]);

        $level2 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $level1->id,
            'title' => 'Level 2',
        ]);

        $level3 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $level2->id,
            'title' => 'Level 3',
        ]);

        $html = $this->menuService->renderMenu('header');

        $this->assertStringContainsString('Level 1', $html);
        $this->assertStringContainsString('Level 2', $html);
        // Level 3 should not be rendered due to max_depth = 2
        $this->assertStringNotContainsString('Level 3', $html);
    }

    /** @test */
    public function it_does_not_render_inactive_menus()
    {
        $menu = Menu::factory()->create([
            'location' => 'header',
            'status' => false, // Inactive
        ]);

        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Home',
        ]);

        $html = $this->menuService->renderMenu('header');

        $this->assertEquals('', $html);
    }

    /** @test */
    public function it_can_update_menu_structure()
    {
        $menu = Menu::factory()->create();

        $item1 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'order' => 0,
        ]);

        $item2 = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'order' => 1,
        ]);

        // Reorder items
        $structure = [
            ['id' => $item2->id, 'children' => []],
            ['id' => $item1->id, 'children' => []],
        ];

        $this->menuService->updateMenuStructure($menu, $structure);

        $item1->refresh();
        $item2->refresh();

        $this->assertEquals(1, $item1->order);
        $this->assertEquals(0, $item2->order);
    }

    /** @test */
    public function it_can_get_available_references()
    {
        $references = $this->menuService->getAvailableReferences();

        $this->assertIsArray($references);
        // The references array structure depends on what modules are available
    }

    /** @test */
    public function menu_item_has_full_url_attribute()
    {
        $menu = Menu::factory()->create();

        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Test',
            'url' => 'https://example.com',
            'type' => 'custom',
        ]);

        $this->assertEquals('https://example.com', $item->full_url);
    }
}
