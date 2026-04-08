<?php

namespace Modules\Blog\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Modules\Blog\Services\BlogPostService;
use Tests\TestCase;

class BlogPostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BlogPostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BlogPostService;
    }

    // ── createPost ────────────────────────────────────────────────────────────

    public function test_create_post_persists_blog_post_to_database(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = $this->service->createPost([
            'title' => 'Nuevo post',
            'status' => 'draft',
            'slug' => 'nuevo-post',
        ]);

        $this->assertInstanceOf(BlogPost::class, $post);
        $this->assertDatabaseHas('blog_posts', ['title' => 'Nuevo post']);
    }

    public function test_create_post_sets_published_at_when_status_is_published(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = $this->service->createPost([
            'title' => 'Post publicado',
            'status' => PostStatus::Published->value,
            'slug' => 'post-publicado',
        ]);

        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->isPast());
    }

    public function test_create_post_does_not_overwrite_provided_published_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $date = now()->subWeek()->startOfSecond();

        $post = $this->service->createPost([
            'title' => 'Post con fecha',
            'status' => PostStatus::Published->value,
            'slug' => 'post-con-fecha',
            'published_at' => $date,
        ]);

        $this->assertEquals($date->toDateTimeString(), $post->published_at->toDateTimeString());
    }

    public function test_create_post_does_not_set_published_at_for_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = $this->service->createPost([
            'title' => 'Borrador',
            'status' => 'draft',
            'slug' => 'borrador',
        ]);

        $this->assertNull($post->published_at);
    }

    public function test_create_post_syncs_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $categories = BlogCategory::factory()->count(2)->create();

        $post = $this->service->createPost([
            'title' => 'Post con categorias',
            'status' => 'draft',
            'slug' => 'post-con-categorias',
            'categories' => $categories->pluck('id')->toArray(),
        ]);

        $this->assertCount(2, $post->fresh()->categories);
    }

    public function test_create_post_syncs_tags(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $tags = BlogTag::factory()->count(3)->create();

        $post = $this->service->createPost([
            'title' => 'Post con tags',
            'status' => 'draft',
            'slug' => 'post-con-tags',
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $this->assertCount(3, $post->fresh()->tags);
    }

    // ── generateSlug ──────────────────────────────────────────────────────────

    public function test_generate_slug_slugifies_title(): void
    {
        $slug = $this->service->generateSlug('Mi Post de Prueba');

        $this->assertEquals('mi-post-de-prueba', $slug);
    }

    public function test_generate_slug_returns_unique_slug_when_slug_exists(): void
    {
        BlogPost::factory()->create(['slug' => 'mi-post']);

        $slug = $this->service->generateSlug('Mi Post');

        $this->assertNotEquals('mi-post', $slug);
        $this->assertStringStartsWith('mi-post', $slug);
    }

    public function test_generate_slug_increments_counter_for_multiple_conflicts(): void
    {
        BlogPost::factory()->create(['slug' => 'post']);
        BlogPost::factory()->create(['slug' => 'post-1']);

        $slug = $this->service->generateSlug('Post');

        $this->assertEquals('post-2', $slug);
    }

    public function test_generate_slug_excludes_own_post_when_updating(): void
    {
        $post = BlogPost::factory()->create(['slug' => 'mi-post']);

        $slug = $this->service->generateSlug('mi-post', $post->id);

        $this->assertEquals('mi-post', $slug);
    }

    // ── syncCategories ────────────────────────────────────────────────────────

    public function test_sync_categories_attaches_new_categories_to_post(): void
    {
        $post = BlogPost::factory()->create();
        $categories = BlogCategory::factory()->count(3)->create();

        $this->service->syncCategories($post, $categories->pluck('id')->toArray());

        $this->assertCount(3, $post->fresh()->categories);
    }

    public function test_sync_categories_replaces_existing_categories(): void
    {
        $post = BlogPost::factory()->create();
        $old = BlogCategory::factory()->count(2)->create();
        $post->categories()->attach($old);

        $new = BlogCategory::factory()->count(1)->create();
        $this->service->syncCategories($post, $new->pluck('id')->toArray());

        $this->assertCount(1, $post->fresh()->categories);
        $this->assertTrue($post->fresh()->categories->contains($new->first()));
    }

    public function test_sync_categories_with_empty_array_detaches_all(): void
    {
        $post = BlogPost::factory()->create();
        $categories = BlogCategory::factory()->count(2)->create();
        $post->categories()->attach($categories);

        $this->service->syncCategories($post, []);

        $this->assertCount(0, $post->fresh()->categories);
    }

    // ── syncTags ──────────────────────────────────────────────────────────────

    public function test_sync_tags_attaches_new_tags_to_post(): void
    {
        $post = BlogPost::factory()->create();
        $tags = BlogTag::factory()->count(2)->create();

        $this->service->syncTags($post, $tags->pluck('id')->toArray());

        $this->assertCount(2, $post->fresh()->tags);
    }

    public function test_sync_tags_replaces_existing_tags(): void
    {
        $post = BlogPost::factory()->create();
        $old = BlogTag::factory()->count(3)->create();
        $post->tags()->attach($old);

        $new = BlogTag::factory()->count(1)->create();
        $this->service->syncTags($post, $new->pluck('id')->toArray());

        $this->assertCount(1, $post->fresh()->tags);
    }

    public function test_sync_tags_with_empty_array_detaches_all(): void
    {
        $post = BlogPost::factory()->create();
        $tags = BlogTag::factory()->count(2)->create();
        $post->tags()->attach($tags);

        $this->service->syncTags($post, []);

        $this->assertCount(0, $post->fresh()->tags);
    }

    // ── getStats ──────────────────────────────────────────────────────────────

    public function test_get_stats_returns_correct_total_count(): void
    {
        BlogPost::factory()->count(5)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(5, $stats['total']);
    }

    public function test_get_stats_returns_correct_published_count(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->draft()->count(2)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(3, $stats['published']);
    }

    public function test_get_stats_returns_correct_draft_count(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->draft()->count(2)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(2, $stats['draft']);
    }

    public function test_get_stats_returns_correct_pending_count(): void
    {
        BlogPost::factory()->pending()->count(1)->create();
        BlogPost::factory()->draft()->count(4)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(1, $stats['pending']);
    }

    public function test_get_stats_returns_zeros_when_no_posts_exist(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['published']);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEquals(0, $stats['pending']);
    }

    // ── deletePost ────────────────────────────────────────────────────────────

    public function test_delete_post_soft_deletes_the_post(): void
    {
        $post = BlogPost::factory()->create();

        $result = $this->service->deletePost($post);

        $this->assertTrue($result);
        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    public function test_delete_post_returns_false_when_already_deleted(): void
    {
        $post = BlogPost::factory()->create();
        $post->delete();

        $result = $this->service->deletePost($post);

        $this->assertFalse($result);
    }

    // ── updatePost ────────────────────────────────────────────────────────────

    public function test_update_post_updates_existing_post(): void
    {
        $post = BlogPost::factory()->draft()->create(['title' => 'Original']);

        $this->service->updatePost($post, ['title' => 'Actualizado']);

        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'title' => 'Actualizado']);
    }

    public function test_update_post_sets_published_at_when_transitioning_to_published(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $updated = $this->service->updatePost($post, ['status' => PostStatus::Published->value]);

        $this->assertNotNull($updated->published_at);
    }

    public function test_update_post_does_not_change_published_at_when_already_published(): void
    {
        $originalDate = now()->subDays(5)->startOfSecond();
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => $originalDate,
        ]);

        $updated = $this->service->updatePost($post, ['title' => 'Nuevo titulo']);

        $this->assertEquals($originalDate->toDateTimeString(), $updated->published_at->toDateTimeString());
    }
}
