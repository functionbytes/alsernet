<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogPostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPermissions([
            'blog.post.view',
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.delete',
            'blog.post.publish',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.delete',
            'blog.post.publish',
        ]);

        $this->editor = User::factory()->create();
        $this->editor->givePermissionTo([
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
        ]);

        $this->guest = User::factory()->create();
    }

    // ========== INDEX ==========

    public function test_admin_can_view_posts_index(): void
    {
        BlogPost::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('blog.posts.index'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::posts.index');
    }

    public function test_editor_can_view_posts_index(): void
    {
        $response = $this->actingAs($this->editor)->get(route('blog.posts.index'));

        $response->assertStatus(200);
    }

    public function test_guest_without_permission_cannot_view_posts_index(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.posts.index'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $response = $this->get(route('blog.posts.index'));

        $response->assertRedirect(route('auth.login'));
    }

    // ========== CREATE ==========

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('blog.posts.create'));

        $response->assertStatus(200);
        $response->assertViewIs('blog::posts.create');
    }

    public function test_guest_without_permission_cannot_view_create_form(): void
    {
        $response = $this->actingAs($this->guest)->get(route('blog.posts.create'));

        $response->assertStatus(403);
    }

    // ========== STORE ==========

    public function test_admin_can_create_post(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.posts.store'), [
            'title' => 'Mi primer post',
            'status' => 'draft',
            'content' => 'Contenido del post',
            'description' => 'Descripcion corta',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', ['title' => 'Mi primer post']);
    }

    public function test_store_validates_required_title(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.posts.store'), [
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_store_validates_required_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.posts.store'), [
            'title' => 'Test Post',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validates_invalid_status_value(): void
    {
        $response = $this->actingAs($this->admin)->post(route('blog.posts.store'), [
            'title' => 'Test Post',
            'status' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_guest_without_create_permission_cannot_store(): void
    {
        $response = $this->actingAs($this->guest)->post(route('blog.posts.store'), [
            'title' => 'Test',
            'status' => 'draft',
        ]);

        $response->assertStatus(403);
    }

    // ========== EDIT ==========

    public function test_admin_can_view_edit_form(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('blog.posts.edit', $post));

        $response->assertStatus(200);
        $response->assertViewIs('blog::posts.edit');
    }

    public function test_editor_can_edit_own_post(): void
    {
        $post = BlogPost::factory()->create(['user_id' => $this->editor->id]);

        $response = $this->actingAs($this->editor)->get(route('blog.posts.edit', $post));

        $response->assertStatus(200);
    }

    public function test_guest_without_permission_cannot_view_edit_form(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->guest)->get(route('blog.posts.edit', $post));

        $response->assertStatus(403);
    }

    // ========== UPDATE ==========

    public function test_admin_can_update_post(): void
    {
        $post = BlogPost::factory()->create(['title' => 'Original']);

        $response = $this->actingAs($this->admin)->put(route('blog.posts.update', $post), [
            'title' => 'Actualizado',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'title' => 'Actualizado']);
    }

    public function test_guest_without_permission_cannot_update(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->guest)->put(route('blog.posts.update', $post), [
            'title' => 'Intento',
            'status' => 'draft',
        ]);

        $response->assertStatus(403);
    }

    // ========== DESTROY ==========

    public function test_admin_can_delete_post(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson(route('blog.posts.destroy', $post));

        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    public function test_editor_without_delete_permission_cannot_delete(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->editor)->deleteJson(route('blog.posts.destroy', $post));

        $response->assertStatus(403);
    }

    public function test_guest_without_permission_cannot_delete(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->guest)->deleteJson(route('blog.posts.destroy', $post));

        $response->assertStatus(403);
    }

    // ========== PUBLISH / UNPUBLISH ==========

    public function test_admin_can_publish_draft_post(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $response = $this->actingAs($this->admin)->post(route('blog.posts.publish', $post));

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'status' => 'published']);
    }

    public function test_admin_can_unpublish_published_post(): void
    {
        $post = BlogPost::factory()->published()->create();

        $response = $this->actingAs($this->admin)->post(route('blog.posts.unpublish', $post));

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'status' => 'draft']);
    }

    public function test_editor_without_publish_permission_cannot_publish(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $response = $this->actingAs($this->editor)->post(route('blog.posts.publish', $post));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_publish_post(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $response = $this->actingAs($this->guest)->post(route('blog.posts.publish', $post));

        $response->assertStatus(403);
    }

    // ========== AJAX SLUG ==========

    public function test_ajax_slug_generation_returns_slug(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('blog.posts.ajax.slug'), [
            'title' => 'Mi Post De Prueba',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['slug']);
        $this->assertStringContainsString('mi-post-de-prueba', $response->json('slug'));
    }

    public function test_ajax_slug_validates_required_title(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('blog.posts.ajax.slug'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_cannot_generate_slug(): void
    {
        $response = $this->postJson(route('blog.posts.ajax.slug'), ['title' => 'Test']);

        $response->assertStatus(401);
    }

    // ========== WITH CATEGORIES AND TAGS ==========

    public function test_post_can_be_created_with_categories_and_tags(): void
    {
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();

        $this->actingAs($this->admin)->post(route('blog.posts.store'), [
            'title' => 'Post con relaciones',
            'status' => 'draft',
            'categories' => [$category->id],
            'tags' => [$tag->id],
        ]);

        $post = BlogPost::where('title', 'Post con relaciones')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
    }

    // ========== BULK ACTION ==========

    public function test_admin_can_bulk_delete_posts(): void
    {
        $posts = BlogPost::factory()->count(3)->create();
        $ids = $posts->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->postJson(route('blog.posts.bulk-action'), [
            'action' => 'delete',
            'ids' => $ids,
        ]);

        $response->assertJson(['success' => true]);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('blog_posts', ['id' => $id]);
        }
    }

    public function test_admin_can_bulk_publish_posts(): void
    {
        $posts = BlogPost::factory()->draft()->count(2)->create();
        $ids = $posts->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->postJson(route('blog.posts.bulk-action'), [
            'action' => 'publish',
            'ids' => $ids,
        ]);

        $response->assertJson(['success' => true]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('blog_posts', ['id' => $id, 'status' => 'published']);
        }
    }

    public function test_bulk_action_validates_empty_ids(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('blog.posts.bulk-action'), [
            'action' => 'delete',
            'ids' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    // ========== HELPERS ==========

    private function createPermissions(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
