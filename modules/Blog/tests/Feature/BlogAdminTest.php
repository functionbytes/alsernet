<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogPost;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $this->ensurePermissionsExist([
            'blog.post.view',
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.delete',
            'blog.post.publish',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['blog.post.view-all', 'blog.post.create', 'blog.post.update', 'blog.post.delete', 'blog.post.publish']);

        return $user;
    }

    private function createUserWithoutPermissions(): User
    {
        $this->ensurePermissionsExist([
            'blog.post.view',
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.delete',
            'blog.post.publish',
        ]);

        return User::factory()->create();
    }

    private function ensurePermissionsExist(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    // ========== AUTHENTICATION ==========

    public function test_guest_cannot_access_admin_post_list(): void
    {
        $response = $this->get(route('blog.posts.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_access_admin_create_form(): void
    {
        $response = $this->get(route('blog.posts.create'));

        $response->assertRedirect(route('auth.login'));
    }

    // ========== AUTHORIZATION ==========

    public function test_user_without_permission_cannot_view_post_list(): void
    {
        $user = $this->createUserWithoutPermissions();

        $response = $this->actingAs($user)->get(route('blog.posts.index'));

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_create_form(): void
    {
        $user = $this->createUserWithoutPermissions();

        $response = $this->actingAs($user)->get(route('blog.posts.create'));

        $response->assertForbidden();
    }

    // ========== ADMIN INDEX ==========

    public function test_authenticated_user_with_permission_can_list_posts(): void
    {
        $admin = $this->createAdmin();
        BlogPost::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('blog.posts.index'));

        $response->assertOk()
            ->assertViewIs('blog::posts.index')
            ->assertViewHas('posts');
    }

    // ========== CREATE POST ==========

    public function test_can_create_blog_post(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('blog.posts.store'), [
            'title' => 'Mi primera entrada',
            'status' => 'draft',
            'content' => 'Contenido del post de prueba',
            'description' => 'Descripcion corta del post',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', ['title' => 'Mi primera entrada', 'status' => 'draft']);
    }

    public function test_create_post_requires_title(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('blog.posts.store'), [
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_create_post_requires_valid_status(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('blog.posts.store'), [
            'title' => 'Post test',
            'status' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_create_post_requires_status_field(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('blog.posts.store'), [
            'title' => 'Post sin estado',
        ]);

        $response->assertSessionHasErrors(['status']);
    }
}
