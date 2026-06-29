<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogTagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPermissions([
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
            'blog.tag.delete',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
            'blog.tag.delete',
        ]);

        $this->guest = User::factory()->create();
    }

    // ========== INDEX ==========

    public function test_admin_can_view_tags_index(): void
    {
        BlogTag::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('blog.tags.index'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::tags.index');
    }

    public function test_guest_without_permission_cannot_view_tags_index(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.tags.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $response = $this->get(route('blog.tags.index'));

        $response->assertRedirect(route('auth.login'));
    }

    // ========== CREATE ==========

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('blog.tags.create'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::tags.create');
    }

    public function test_guest_without_permission_cannot_view_create_form(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.tags.create'));

        $response->assertStatus(403);
    }

    // ========== STORE ==========

    public function test_admin_can_create_tag(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.tags.store'), [
            'name' => 'Laravel',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('blog.tags.index'));
        $this->assertDatabaseHas('blog_tags', ['name' => 'Laravel']);
    }

    public function test_store_auto_generates_slug_when_empty(): void
    {
        $this->actingAs($this->admin)->post(route('blog.tags.store'), [
            'name' => 'Mi Etiqueta Nueva',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('blog_tags', ['slug' => 'mi-etiqueta-nueva']);
    }

    public function test_store_validates_name_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.tags.store'), [
            'status' => 'published',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_status_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.tags.store'), [
            'name' => 'Laravel',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validates_invalid_status_value(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.tags.store'), [
            'name' => 'Laravel',
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_guest_cannot_create_tag(): void
    {
        $response = $this->actingAs($this->guest)->post(route('blog.tags.store'), [
            'name' => 'Etiqueta',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('blog_tags', ['name' => 'Etiqueta']);
    }

    // ========== EDIT ==========

    public function test_admin_can_view_edit_form(): void
    {
        $tag = BlogTag::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('blog.tags.edit', $tag));

        $response->assertStatus(200);
        $response->assertViewIs('blog::tags.edit');
    }

    public function test_guest_without_permission_cannot_view_edit_form(): void
    {
        $tag = BlogTag::factory()->create();

        $response = $this->actingAs($this->guest)->get(route('blog.tags.edit', $tag));

        $response->assertStatus(403);
    }

    // ========== UPDATE ==========

    public function test_admin_can_update_tag(): void
    {
        $tag = BlogTag::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($this->admin)->put(route('blog.tags.update', $tag), [
            'name' => 'Actualizada',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('blog.tags.index'));
        $this->assertDatabaseHas('blog_tags', ['id' => $tag->id, 'name' => 'Actualizada']);
    }

    public function test_guest_cannot_update_tag(): void
    {
        $tag = BlogTag::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($this->guest)->put(route('blog.tags.update', $tag), [
            'name' => 'Intento',
            'status' => 'published',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('blog_tags', ['name' => 'Intento']);
    }

    // ========== DESTROY ==========

    public function test_admin_can_delete_tag(): void
    {
        $tag = BlogTag::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('blog.tags.destroy', $tag));

        $response->assertRedirect(route('blog.tags.index'));
        $this->assertDatabaseMissing('blog_tags', ['id' => $tag->id]);
    }

    public function test_delete_detaches_posts_before_deleting(): void
    {
        $tag = BlogTag::factory()->create();

        // Need blog.post.create and blog.post.view-all permissions for this fixture
        Permission::firstOrCreate(['name' => 'blog.post.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'blog.post.view-all', 'guard_name' => 'web']);
        $this->admin->givePermissionTo(['blog.post.create', 'blog.post.view-all']);

        $post = BlogPost::factory()->create();
        $post->tags()->attach($tag->id);

        $this->actingAs($this->admin)->delete(route('blog.tags.destroy', $tag));

        $this->assertDatabaseEmpty('blog_post_tags');
    }

    public function test_guest_cannot_delete_tag(): void
    {
        $tag = BlogTag::factory()->create();

        $response = $this->actingAs($this->guest)->delete(route('blog.tags.destroy', $tag));

        $response->assertStatus(403);
        $this->assertDatabaseHas('blog_tags', ['id' => $tag->id]);
    }

    // ========== AJAX ALL ==========

    public function test_ajax_all_returns_published_tags(): void
    {
        BlogTag::factory()->count(3)->create(['status' => 'published']);
        BlogTag::factory()->count(2)->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin)->getJson(route('blog.tags.ajax.all'));

        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonStructure([['id', 'name', 'slug']]);
    }

    public function test_ajax_all_returns_only_id_name_and_slug(): void
    {
        BlogTag::factory()->create(['name' => 'Laravel', 'status' => 'published']);

        $response = $this->actingAs($this->admin)->getJson(route('blog.tags.ajax.all'));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Laravel']);

        $keys = array_keys($response->json()[0]);
        $this->assertEqualsCanonicalizing(['id', 'name', 'slug'], $keys);
    }

    public function test_ajax_all_returns_empty_when_no_published_tags(): void
    {
        BlogTag::factory()->count(2)->draft()->create();

        $response = $this->actingAs($this->admin)->getJson(route('blog.tags.ajax.all'));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_unauthenticated_cannot_call_ajax_all(): void
    {
        $response = $this->getJson(route('blog.tags.ajax.all'));

        $response->assertStatus(401);
    }

    // ========== HELPERS ==========

    private function createPermissions(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
