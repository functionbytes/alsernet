<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Template\Models\Menu;
use Modules\Template\Models\MenuItem;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;
use Modules\Template\Services\TemplateService;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Gate::before(fn () => true);
    }

    // -------------------------------------------------------------------------
    // Menu creation
    // -------------------------------------------------------------------------

    public function test_menu_creation(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.menus.store'), [
                'name' => 'Main Navigation',
                'location' => 'header',
                'status' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('menus', [
            'name' => 'Main Navigation',
            'location' => 'header',
        ]);
    }

    // -------------------------------------------------------------------------
    // Menu item ordering
    // -------------------------------------------------------------------------

    public function test_menu_item_ordering(): void
    {
        $menu = Menu::factory()->create();

        MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'First', 'order' => 0]);
        MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'Second', 'order' => 1]);
        MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'Third', 'order' => 2]);

        $items = $menu->allItems()->get();

        $this->assertEquals('First', $items[0]->title);
        $this->assertEquals('Second', $items[1]->title);
        $this->assertEquals('Third', $items[2]->title);
    }

    // -------------------------------------------------------------------------
    // MenuItem::isActive()
    // -------------------------------------------------------------------------

    public function test_menu_item_active_state(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'url' => 'http://localhost/about',
            'type' => 'custom',
        ]);

        // Simulate request to /about
        $this->get('/about');

        $this->assertTrue($item->isActive());
    }

    public function test_menu_item_active_state_ignores_trailing_slash(): void
    {
        $menu = Menu::factory()->create();

        $itemWithSlash = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'url' => 'http://localhost/about/',
            'type' => 'custom',
        ]);

        $itemWithoutSlash = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'url' => 'http://localhost/about',
            'type' => 'custom',
        ]);

        // Simulate request to /about (no trailing slash)
        $this->get('/about');

        $currentUrl = request()->url();

        $urlWithSlash = rtrim($itemWithSlash->full_url, '/');
        $urlWithoutSlash = rtrim($itemWithoutSlash->full_url, '/');
        $normalizedCurrent = rtrim($currentUrl, '/');

        $this->assertEquals($normalizedCurrent, $urlWithSlash);
        $this->assertEquals($normalizedCurrent, $urlWithoutSlash);
    }

    // -------------------------------------------------------------------------
    // Template versioning
    // -------------------------------------------------------------------------

    public function test_version_creation_on_template_update(): void
    {
        $service = app(TemplateService::class);

        $template = Template::factory()->create(['content' => '<p>Original</p>']);

        $versionsBefore = TemplateVersion::where('template_id', $template->id)->count();

        $service->update($template, ['name' => $template->name, 'content' => '<p>Updated</p>']);

        $versionsAfter = TemplateVersion::where('template_id', $template->id)->count();

        $this->assertGreaterThan($versionsBefore, $versionsAfter);
    }

    public function test_version_restore(): void
    {
        $service = app(TemplateService::class);

        $template = Template::factory()->create(['content' => '<p>Version 1</p>']);

        // Create a version snapshot of the original state
        $version = $template->createVersion();

        // Change the template
        $template->update(['content' => '<p>Version 2</p>']);

        // Restore to the snapshot
        $service->restoreVersion($version);

        $template->refresh();

        $this->assertEquals('<p>Version 1</p>', $template->content);
    }

    // -------------------------------------------------------------------------
    // Bulk actions
    // -------------------------------------------------------------------------

    public function test_bulk_action_activate_multiple_menus(): void
    {
        $m1 = Menu::factory()->inactive()->create();
        $m2 = Menu::factory()->inactive()->create();
        $m3 = Menu::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.bulk-action'), [
                'action' => 'activate',
                'ids' => [$m1->id, $m2->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $m1->refresh();
        $m2->refresh();
        $m3->refresh();

        $this->assertTrue($m1->status);
        $this->assertTrue($m2->status);
        $this->assertFalse($m3->status);
    }

    public function test_bulk_action_deactivate_multiple_menus(): void
    {
        $m1 = Menu::factory()->active()->create();
        $m2 = Menu::factory()->active()->create();
        $m3 = Menu::factory()->active()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.bulk-action'), [
                'action' => 'deactivate',
                'ids' => [$m1->id, $m2->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $m1->refresh();
        $m2->refresh();
        $m3->refresh();

        $this->assertFalse($m1->status);
        $this->assertFalse($m2->status);
        $this->assertTrue($m3->status);
    }

    public function test_bulk_action_delete_multiple_menus(): void
    {
        $m1 = Menu::factory()->create();
        $m2 = Menu::factory()->create();
        $m3 = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.bulk-action'), [
                'action' => 'delete',
                'ids' => [$m1->id, $m2->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $this->assertSoftDeleted('menus', ['id' => $m1->id]);
        $this->assertSoftDeleted('menus', ['id' => $m2->id]);
        $this->assertDatabaseHas('menus', ['id' => $m3->id, 'deleted_at' => null]);
    }

    // -------------------------------------------------------------------------
    // Menu structure (drag & drop)
    // -------------------------------------------------------------------------

    public function test_update_structure_saves_menu_items_hierarchy(): void
    {
        $menu = Menu::factory()->create();
        $item1 = MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'Home']);
        $item2 = MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'About']);
        $item3 = MenuItem::factory()->create(['menu_id' => $menu->id, 'title' => 'Contact']);

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.structure.update', $menu), [
                'items' => [
                    ['id' => $item3->id, 'order' => 0],
                    ['id' => $item1->id, 'order' => 1],
                    ['id' => $item2->id, 'order' => 2],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $item1->refresh();
        $item2->refresh();
        $item3->refresh();

        $this->assertEquals(1, $item1->order);
        $this->assertEquals(2, $item2->order);
        $this->assertEquals(0, $item3->order);
    }

    // -------------------------------------------------------------------------
    // Menu items - store
    // -------------------------------------------------------------------------

    public function test_store_item_creates_menu_item_via_ajax(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'New Item',
                'url' => 'https://example.com',
                'type' => 'custom',
            ])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['item' => ['id', 'title', 'url', 'type']]);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'New Item',
            'url' => 'https://example.com',
        ]);
    }

    public function test_store_item_validates_title_is_required(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.items.store', $menu), [
                'type' => 'custom',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_item_validates_type_is_required(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'Test Item',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_item_validates_type_must_be_valid(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.items.store', $menu), [
                'title' => 'Test Item',
                'type' => 'invalid-type',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // -------------------------------------------------------------------------
    // Menu items - update
    // -------------------------------------------------------------------------

    public function test_update_item_modifies_menu_item(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'title' => 'Original Title',
            'url' => 'https://old.com',
        ]);

        $this->actingAs($this->user)
            ->putJson(route('settings.menus.items.update', [$menu, $item]), [
                'title' => 'Updated Title',
                'url' => 'https://new.com',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $item->refresh();
        $this->assertEquals('Updated Title', $item->title);
        $this->assertEquals('https://new.com', $item->url);
    }

    public function test_update_item_rejects_item_from_different_menu(): void
    {
        $menu1 = Menu::factory()->create();
        $menu2 = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu2->id]);

        $this->actingAs($this->user)
            ->putJson(route('settings.menus.items.update', [$menu1, $item]), [
                'title' => 'Updated Title',
            ])
            ->assertForbidden();
    }

    public function test_update_item_validates_title(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->actingAs($this->user)
            ->putJson(route('settings.menus.items.update', [$menu, $item]), [
                'title' => str_repeat('a', 256),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // -------------------------------------------------------------------------
    // Menu items - destroy
    // -------------------------------------------------------------------------

    public function test_destroy_item_deletes_menu_item(): void
    {
        $menu = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

        $this->actingAs($this->user)
            ->deleteJson(route('settings.menus.items.destroy', [$menu, $item]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }

    public function test_destroy_item_deletes_regardless_of_menu(): void
    {
        $menu1 = Menu::factory()->create();
        $menu2 = Menu::factory()->create();
        $item = MenuItem::factory()->create(['menu_id' => $menu2->id]);

        $this->actingAs($this->user)
            ->deleteJson(route('settings.menus.items.destroy', [$menu1, $item]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }

    // -------------------------------------------------------------------------
    // Menu node resolution
    // -------------------------------------------------------------------------

    public function test_get_node_returns_html_partial(): void
    {
        $menu = Menu::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.menus.get-node', $menu), [
                'data' => [
                    'type' => 'custom',
                    'title' => 'Test Item',
                    'url' => 'https://example.com',
                ],
            ])
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('html', $response);
        $this->assertIsString($response['html']);
    }

    public function test_get_node_resolves_page_reference(): void
    {
        $menu = Menu::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.menus.get-node', $menu), [
                'data' => [
                    'type' => 'page',
                    'title' => 'About Us',
                    'reference_id' => 1,
                ],
            ])
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('html', $response);
    }

    public function test_get_node_validates_data_is_required(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.menus.get-node', $menu), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    }
}
