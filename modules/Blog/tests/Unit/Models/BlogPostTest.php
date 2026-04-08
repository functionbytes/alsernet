<?php

namespace Modules\Blog\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function test_published_scope_returns_only_published_posts(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->draft()->count(2)->create();

        $this->assertCount(3, BlogPost::published()->get());
    }

    public function test_published_scope_excludes_future_published_at(): void
    {
        BlogPost::factory()->published()->create();
        BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->assertCount(1, BlogPost::published()->get());
    }

    public function test_featured_scope_returns_only_featured_posts(): void
    {
        BlogPost::factory()->featured()->count(2)->create();
        BlogPost::factory()->count(3)->create(['is_featured' => false]);

        $this->assertCount(2, BlogPost::featured()->get());
    }

    public function test_draft_scope_returns_only_draft_posts(): void
    {
        BlogPost::factory()->draft()->count(2)->create();
        BlogPost::factory()->published()->count(3)->create();

        $this->assertCount(2, BlogPost::draft()->get());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_status_is_cast_to_post_status_enum(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $this->assertInstanceOf(PostStatus::class, $post->status);
        $this->assertEquals(PostStatus::Draft, $post->status);
    }

    public function test_published_status_is_cast_to_enum(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->assertEquals(PostStatus::Published, $post->status);
    }

    public function test_is_featured_is_cast_to_boolean(): void
    {
        $post = BlogPost::factory()->featured()->create();

        $this->assertIsBool($post->is_featured);
        $this->assertTrue($post->is_featured);
    }

    public function test_views_is_cast_to_integer(): void
    {
        $post = BlogPost::factory()->create(['views' => 42]);

        $this->assertIsInt($post->views);
        $this->assertSame(42, $post->views);
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->assertInstanceOf(Carbon::class, $post->published_at);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function test_url_attribute_contains_blog_slug_path(): void
    {
        $post = BlogPost::factory()->create(['slug' => 'mi-post-de-prueba']);

        $this->assertStringContainsString('/blog/mi-post-de-prueba', $post->url);
    }

    public function test_excerpt_returns_description_when_available(): void
    {
        $post = BlogPost::factory()->create([
            'description' => 'Descripcion corta',
            'content' => 'Contenido largo del post',
        ]);

        $this->assertEquals('Descripcion corta', $post->excerpt);
    }

    public function test_excerpt_falls_back_to_stripped_content_when_no_description(): void
    {
        $post = BlogPost::factory()->create([
            'description' => null,
            'content' => '<p>Contenido del post</p>',
        ]);

        $this->assertStringContainsString('Contenido del post', $post->excerpt);
        $this->assertStringNotContainsString('<p>', $post->excerpt);
    }

    public function test_excerpt_is_truncated_to_200_characters(): void
    {
        $post = BlogPost::factory()->create([
            'description' => null,
            'content' => str_repeat('a', 300),
        ]);

        $this->assertSame(200, mb_strlen($post->excerpt));
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_post_belongs_to_many_categories(): void
    {
        $post = BlogPost::factory()->create();
        $categories = BlogCategory::factory()->count(2)->create();
        $post->categories()->attach($categories);

        $this->assertCount(2, $post->fresh()->categories);
    }

    public function test_post_belongs_to_many_tags(): void
    {
        $post = BlogPost::factory()->create();
        $tags = BlogTag::factory()->count(3)->create();
        $post->tags()->attach($tags);

        $this->assertCount(3, $post->fresh()->tags);
    }

    public function test_post_categories_returns_empty_collection_when_none_attached(): void
    {
        $post = BlogPost::factory()->create();

        $this->assertCount(0, $post->categories);
    }

    public function test_post_tags_returns_empty_collection_when_none_attached(): void
    {
        $post = BlogPost::factory()->create();

        $this->assertCount(0, $post->tags);
    }

    // ── isPublished() ─────────────────────────────────────────────────────────

    public function test_is_published_returns_true_for_published_post_with_past_date(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->assertTrue($post->isPublished());
    }

    public function test_is_published_returns_false_for_draft_post(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $this->assertFalse($post->isPublished());
    }

    public function test_is_published_returns_false_for_pending_post(): void
    {
        $post = BlogPost::factory()->pending()->create();

        $this->assertFalse($post->isPublished());
    }

    public function test_is_published_returns_false_when_published_at_is_in_future(): void
    {
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->addHour(),
        ]);

        $this->assertFalse($post->isPublished());
    }

    public function test_is_published_returns_false_when_published_at_is_null(): void
    {
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => null,
        ]);

        $this->assertFalse($post->isPublished());
    }

    // ── Soft Deletes ──────────────────────────────────────────────────────────

    public function test_post_can_be_soft_deleted(): void
    {
        $post = BlogPost::factory()->create();
        $post->delete();

        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    public function test_soft_deleted_post_is_excluded_from_default_query(): void
    {
        $post = BlogPost::factory()->create();
        $post->delete();

        $this->assertNull(BlogPost::find($post->id));
    }

    public function test_soft_deleted_post_is_retrievable_with_trashed(): void
    {
        $post = BlogPost::factory()->create();
        $post->delete();

        $this->assertNotNull(BlogPost::withTrashed()->find($post->id));
    }
}
