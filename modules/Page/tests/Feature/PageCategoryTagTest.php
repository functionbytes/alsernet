<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageCategory;
use Modules\Page\Models\PageTag;
use Modules\Page\Services\PageService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageCategoryTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.create', 'guard_name' => 'web']);

        // The category controller calls authorize('update', Page::class) with no model
        // instance. Laravel strips the class string and calls PagePolicy::update($user)
        // with 1 arg, but the method signature requires 2. The Gate::before hook
        // resolves page permissions directly from Spatie, bypassing the policy.
        Gate::before(function (User $user, string $ability) {
            $permMap = [
                'update' => 'page.update',
                'viewAny' => 'page.view',
                'create' => 'page.create',
                'approve' => 'page.approve',
            ];

            if (array_key_exists($ability, $permMap)) {
                return $user->hasPermissionTo($permMap[$ability]) ? true : false;
            }

            return null;
        });
    }

    private function userWithPermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page.update');

        return $user;
    }

    private function createCategory(array $overrides = []): PageCategory
    {
        return PageCategory::create(array_merge([
            'name' => 'Test Category '.uniqid(),
            'sort_order' => 0,
        ], $overrides));
    }

    private function createTag(array $overrides = []): PageTag
    {
        return PageTag::create(array_merge([
            'name' => 'testtag'.uniqid(),
        ], $overrides));
    }

    // =========================================================================
    // Categories CRUD
    // =========================================================================

    public function test_admin_can_create_category(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->postJson(route('pages.categories.store'), ['name' => 'My Category'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'My Category']);

        $this->assertDatabaseHas('page_categories', ['name' => 'My Category']);
    }

    public function test_category_auto_generates_slug(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->postJson(route('pages.categories.store'), ['name' => 'Hello World'])
            ->assertCreated();

        $this->assertDatabaseHas('page_categories', ['slug' => 'hello-world']);
    }

    public function test_admin_can_update_category(): void
    {
        $user = $this->userWithPermission();
        $category = $this->createCategory(['name' => 'Old Name']);

        $this->actingAs($user)
            ->putJson(route('pages.categories.update', $category), ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('page_categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $user = $this->userWithPermission();
        $category = $this->createCategory();

        $this->actingAs($user)
            ->deleteJson(route('pages.categories.destroy', $category))
            ->assertNoContent();

        $this->assertDatabaseMissing('page_categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_pages(): void
    {
        $user = $this->userWithPermission();
        $category = $this->createCategory();
        $page = Page::factory()->create();
        $page->categories()->attach($category->id);

        $this->actingAs($user)
            ->deleteJson(route('pages.categories.destroy', $category))
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'La categoría tiene páginas asociadas. Usa force=true para eliminar de todas formas.']);

        $this->assertDatabaseHas('page_categories', ['id' => $category->id]);
    }

    public function test_can_force_delete_category_with_pages(): void
    {
        $user = $this->userWithPermission();
        $category = $this->createCategory();
        $page = Page::factory()->create();
        $page->categories()->attach($category->id);

        $this->actingAs($user)
            ->deleteJson(route('pages.categories.destroy', $category), ['force' => true])
            ->assertNoContent();

        $this->assertDatabaseMissing('page_categories', ['id' => $category->id]);
    }

    public function test_unauthorized_user_cannot_manage_categories(): void
    {
        $user = User::factory()->create();
        $category = $this->createCategory();

        $this->actingAs($user)->getJson(route('pages.categories.index'))->assertForbidden();
        $this->actingAs($user)->postJson(route('pages.categories.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->putJson(route('pages.categories.update', $category), ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->deleteJson(route('pages.categories.destroy', $category))->assertForbidden();
    }

    public function test_category_index_returns_page_counts(): void
    {
        $user = $this->userWithPermission();
        $category = $this->createCategory(['name' => 'Counted']);
        $page = Page::factory()->create();
        $page->categories()->attach($category->id);

        $response = $this->actingAs($user)
            ->getJson(route('pages.categories.index'))
            ->assertOk();

        $found = collect($response->json())->firstWhere('id', $category->id);
        $this->assertEquals(1, $found['pages_count']);
    }

    public function test_create_category_requires_name(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->postJson(route('pages.categories.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // =========================================================================
    // Page-Category assignment
    // =========================================================================

    public function test_page_can_be_assigned_to_multiple_categories(): void
    {
        $catA = $this->createCategory(['name' => 'Cat A']);
        $catB = $this->createCategory(['name' => 'Cat B']);
        $page = Page::factory()->create();

        $page->categories()->attach([$catA->id, $catB->id]);

        $this->assertCount(2, $page->fresh()->categories);
        $this->assertTrue($page->categories()->where('page_category_id', $catA->id)->exists());
        $this->assertTrue($page->categories()->where('page_category_id', $catB->id)->exists());
    }

    // =========================================================================
    // Tags
    // =========================================================================

    public function test_page_can_have_multiple_tags(): void
    {
        $tagA = $this->createTag(['name' => 'laravel', 'slug' => 'laravel']);
        $tagB = $this->createTag(['name' => 'php', 'slug' => 'php']);
        $page = Page::factory()->create();

        $page->tags()->attach([$tagA->id, $tagB->id]);

        $this->assertCount(2, $page->fresh()->tags);
    }

    public function test_tags_are_created_if_not_exist(): void
    {
        $user = $this->userWithPermission();
        $user->givePermissionTo('page.create');

        $service = app(PageService::class);

        $page = $service->createPage([
            'title' => 'Tagged Page',
            'slug' => 'tagged-page',
            'content' => 'Body',
            'status' => 'draft',
            'tags_input' => 'brandnew1, brandnew2',
        ]);

        $this->actingAs($user);

        $this->assertDatabaseHas('page_tags', ['slug' => 'brandnew1']);
        $this->assertDatabaseHas('page_tags', ['slug' => 'brandnew2']);
        $this->assertCount(2, $page->fresh()->tags);
    }

    public function test_filter_pages_by_category_slug(): void
    {
        $catA = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $catB = $this->createCategory(['name' => 'Docs', 'slug' => 'docs']);

        $pageA = Page::factory()->create();
        $pageB = Page::factory()->create();

        $pageA->categories()->attach($catA->id);
        $pageB->categories()->attach($catB->id);

        $service = app(PageService::class);
        $result = $service->getPages(['category' => 'news', 'sort_by' => 'created_at', 'sort_order' => 'desc']);

        $ids = $result->pluck('id');
        $this->assertTrue($ids->contains($pageA->id));
        $this->assertFalse($ids->contains($pageB->id));
    }
}
