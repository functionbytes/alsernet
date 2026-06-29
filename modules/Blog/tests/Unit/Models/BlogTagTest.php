<?php

namespace Modules\Blog\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Tests\TestCase;

class BlogTagTest extends TestCase
{
    use RefreshDatabase;

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function test_published_scope_returns_only_published_tags(): void
    {
        BlogTag::factory()->count(3)->create(['status' => 'published']);
        BlogTag::factory()->draft()->count(2)->create();

        $this->assertCount(3, BlogTag::published()->get());
    }

    public function test_published_scope_excludes_draft_tags(): void
    {
        $draft = BlogTag::factory()->draft()->create();

        $results = BlogTag::published()->get();

        $this->assertFalse($results->contains($draft));
    }

    public function test_active_scope_behaves_same_as_published(): void
    {
        BlogTag::factory()->count(2)->create(['status' => 'published']);
        BlogTag::factory()->draft()->create();

        $this->assertCount(2, BlogTag::active()->get());
    }

    // ── Accessor ──────────────────────────────────────────────────────────────

    public function test_url_attribute_contains_blog_tag_slug_path(): void
    {
        $tag = BlogTag::factory()->create(['slug' => 'laravel']);

        $this->assertStringContainsString('/blog/tag/laravel', $tag->url);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_posts_relationship_returns_associated_posts(): void
    {
        $tag = BlogTag::factory()->create();
        $posts = BlogPost::factory()->count(3)->create();
        $tag->posts()->attach($posts);

        $this->assertCount(3, $tag->fresh()->posts);
    }

    public function test_posts_returns_empty_collection_when_none_attached(): void
    {
        $tag = BlogTag::factory()->create();

        $this->assertCount(0, $tag->posts);
    }

    public function test_tag_can_be_attached_to_multiple_posts(): void
    {
        $tag = BlogTag::factory()->create();
        $postA = BlogPost::factory()->create();
        $postB = BlogPost::factory()->create();
        $tag->posts()->attach([$postA->id, $postB->id]);

        $attached = $tag->fresh()->posts;

        $this->assertCount(2, $attached);
        $this->assertTrue($attached->contains($postA));
        $this->assertTrue($attached->contains($postB));
    }
}
