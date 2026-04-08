<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPermissions([
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.category.delete',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.category.delete',
        ]);

        $this->guest = User::factory()->create();
    }

    // ========== INDEX ==========

    public function test_admin_can_view_categories_index(): void
    {
        BlogCategory::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('blog.categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::categories.index');
    }

    public function test_guest_without_permission_cannot_view_categories_index(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.categories.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $response = $this->get(route('blog.categories.index'));

        $response->assertRedirect(route('auth.login'));
    }

    // ========== CREATE ==========

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('blog.categories.create'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::categories.create');
    }

    public function test_guest_without_permission_cannot_view_create_form(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.categories.create'));

        $response->assertStatus(403);
    }

    // ========== STORE ==========

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'name' => 'Tecnologia',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('blog.categories.index'));
        $this->assertDatabaseHas('blog_categories', ['name' => 'Tecnologia']);
    }

    public function test_store_auto_generates_slug_when_empty(): void
    {
        $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'name' => 'Mi Categoria Nueva',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('blog_categories', ['slug' => 'mi-categoria-nueva']);
    }

    public function test_store_validates_name_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'status' => 'published',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_status_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'name' => 'Test',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validates_invalid_status_value(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'name' => 'Test',
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_guest_cannot_create_category(): void
    {
        $response = $this->actingAs($this->guest)->post(route('blog.categories.store'), [
            'name' => 'Categoria',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('blog_categories', ['name' => 'Categoria']);
    }

    // ========== EDIT ==========

    public function test_admin_can_view_edit_form(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('blog.categories.edit', $category));

        $response->assertStatus(200);
        $response->assertViewIs('blog::categories.edit');
    }

    public function test_guest_without_permission_cannot_view_edit_form(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->guest)->get(route('blog.categories.edit', $category));

        $response->assertStatus(403);
    }

    // ========== UPDATE ==========

    public function test_admin_can_update_category(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($this->admin)->put(route('blog.categories.update', $category), [
            'name' => 'Actualizada',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('blog.categories.index'));
        $this->assertDatabaseHas('blog_categories', ['id' => $category->id, 'name' => 'Actualizada']);
    }

    public function test_update_auto_generates_slug_when_empty(): void
    {
        $category = BlogCategory::factory()->create();

        $this->actingAs($this->admin)->put(route('blog.categories.update', $category), [
            'name' => 'Nombre Actualizado',
            'status' => 'published',
            'slug' => '',
        ]);

        $this->assertDatabaseHas('blog_categories', [
            'id' => $category->id,
            'slug' => 'nombre-actualizado',
        ]);
    }

    public function test_guest_cannot_update_category(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($this->guest)->put(route('blog.categories.update', $category), [
            'name' => 'Intento',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('blog_categories', ['name' => 'Intento']);
    }

    // ========== DESTROY ==========

    public function test_admin_can_delete_category(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('blog.categories.destroy', $category));

        $response->assertRedirect(route('blog.categories.index'));
        $this->assertSoftDeleted('blog_categories', ['id' => $category->id]);
    }

    public function test_delete_detaches_posts_before_deleting(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('blog.categories.destroy', $category));

        $response->assertRedirect();
        $this->assertDatabaseEmpty('blog_post_categories');
    }

    public function test_guest_cannot_delete_category(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->guest)->delete(route('blog.categories.destroy', $category));

        $response->assertStatus(403);
        $this->assertDatabaseHas('blog_categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_child_category(): void
    {
        $parent = BlogCategory::factory()->create();

        $this->actingAs($this->admin)->post(route('blog.categories.store'), [
            'name' => 'Hijo',
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('blog_categories', [
            'name' => 'Hijo',
            'parent_id' => $parent->id,
        ]);
    }

    // ========== HELPERS ==========

    private function createPermissions(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
