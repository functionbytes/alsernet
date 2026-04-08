<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogPost;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogPoliciesTest extends TestCase
{
    use RefreshDatabase;

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
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.category.delete',
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
            'blog.tag.delete',
        ]);
    }

    // ========== BlogPostPolicy: viewAny ==========

    public function test_user_with_view_all_permission_can_see_any_post_in_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.post.view-all');

        $response = $this->actingAs($user)->get(route('blog.posts.index'));

        $response->assertOk();
    }

    public function test_user_without_permission_cannot_access_posts_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('blog.posts.index'));

        $response->assertForbidden();
    }

    // ========== BlogPostPolicy: update ==========

    public function test_user_can_update_own_post_without_view_all(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.post.update');

        $post = BlogPost::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('blog.posts.edit', $post));

        $response->assertOk();
    }

    public function test_user_cannot_update_others_post_without_view_all(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $other->givePermissionTo('blog.post.update');

        $post = BlogPost::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->get(route('blog.posts.edit', $post));

        $response->assertForbidden();
    }

    // ========== BlogPostPolicy: publish ==========

    public function test_user_with_publish_permission_can_publish_post(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.post.publish');

        $post = BlogPost::factory()->draft()->create();

        $response = $this->actingAs($user)->postJson(route('blog.posts.publish', $post));

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_user_without_publish_permission_cannot_publish(): void
    {
        $user = User::factory()->create();
        // No publish permission assigned

        $post = BlogPost::factory()->draft()->create();

        $response = $this->actingAs($user)->post(route('blog.posts.publish', $post));

        $response->assertForbidden();
    }

    // ========== BlogPostPolicy: delete ==========

    public function test_user_with_delete_permission_can_delete_post(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.post.delete');

        $post = BlogPost::factory()->create();

        $response = $this->actingAs($user)->deleteJson(route('blog.posts.destroy', $post));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    // ========== BlogCategoryPolicy ==========

    public function test_user_with_category_view_permission_can_access_categories(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.category.view');

        $response = $this->actingAs($user)->get(route('blog.categories.index'));

        $response->assertOk();
    }

    public function test_user_without_category_permission_cannot_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('blog.categories.index'));

        $response->assertForbidden();
    }

    // ========== BlogTagPolicy ==========

    public function test_user_with_tag_view_permission_can_access_tags(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.tag.view');

        $response = $this->actingAs($user)->get(route('blog.tags.index'));

        $response->assertOk();
    }

    // ========== HELPERS ==========

    private function createPermissions(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
