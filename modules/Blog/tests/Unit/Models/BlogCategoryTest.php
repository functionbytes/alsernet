<?php

namespace Modules\Blog\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Tests\TestCase;

class BlogCategoryTest extends TestCase
{
    use RefreshDatabase;

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function test_root_scope_returns_categories_without_parent(): void
    {
        $root = BlogCategory::factory()->count(3)->create(['parent_id' => 0]);
        $parent = BlogCategory::factory()->create(['parent_id' => 0]);
        BlogCategory::factory()->count(2)->create(['parent_id' => $parent->id]);

        $this->assertCount(4, BlogCategory::root()->get());
    }

    public function test_published_scope_returns_only_published_categories(): void
    {
        BlogCategory::factory()->count(2)->create(['status' => 'published']);
        BlogCategory::factory()->draft()->count(3)->create();

        $this->assertCount(2, BlogCategory::published()->get());
    }

    public function test_published_scope_excludes_draft_categories(): void
    {
        $draft = BlogCategory::factory()->draft()->create();

        $results = BlogCategory::published()->get();

        $this->assertFalse($results->contains($draft));
    }

    public function test_featured_scope_returns_only_featured_categories(): void
    {
        BlogCategory::factory()->featured()->count(2)->create();
        BlogCategory::factory()->count(3)->create(['is_featured' => false]);

        $this->assertCount(2, BlogCategory::featured()->get());
    }

    public function test_ordered_scope_sorts_by_order_ascending(): void
    {
        BlogCategory::factory()->create(['order' => 10]);
        BlogCategory::factory()->create(['order' => 1]);
        BlogCategory::factory()->create(['order' => 5]);

        $orders = BlogCategory::ordered()->pluck('order')->toArray();

        $this->assertEquals([1, 5, 10], $orders);
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_is_featured_is_cast_to_boolean(): void
    {
        $category = BlogCategory::factory()->featured()->create();

        $this->assertIsBool($category->is_featured);
        $this->assertTrue($category->is_featured);
    }

    public function test_is_default_is_cast_to_boolean(): void
    {
        $category = BlogCategory::factory()->default()->create();

        $this->assertIsBool($category->is_default);
        $this->assertTrue($category->is_default);
    }

    public function test_order_is_cast_to_integer(): void
    {
        $category = BlogCategory::factory()->create(['order' => 3]);

        $this->assertIsInt($category->order);
        $this->assertSame(3, $category->order);
    }

    public function test_parent_id_is_cast_to_integer(): void
    {
        $category = BlogCategory::factory()->create(['parent_id' => 0]);

        $this->assertIsInt($category->parent_id);
    }

    // ── Accessor ──────────────────────────────────────────────────────────────

    public function test_url_attribute_contains_blog_category_slug_path(): void
    {
        $category = BlogCategory::factory()->create(['slug' => 'mi-categoria']);

        $this->assertStringContainsString('/blog/category/mi-categoria', $category->url);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_children_relationship_returns_child_categories(): void
    {
        $parent = BlogCategory::factory()->create(['parent_id' => 0]);
        $children = BlogCategory::factory()->count(3)->create(['parent_id' => $parent->id]);

        $this->assertCount(3, $parent->children);
        $this->assertTrue($parent->children->contains($children->first()));
    }

    public function test_children_returns_empty_collection_for_leaf_category(): void
    {
        $category = BlogCategory::factory()->create(['parent_id' => 0]);

        $this->assertCount(0, $category->children);
    }

    public function test_parent_relationship_returns_parent_category(): void
    {
        $parent = BlogCategory::factory()->create(['parent_id' => 0]);
        $child = BlogCategory::factory()->child($parent->id)->create();

        $this->assertInstanceOf(BlogCategory::class, $child->parent);
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_parent_relationship_returns_default_when_no_parent(): void
    {
        $category = BlogCategory::factory()->create(['parent_id' => 0]);

        // withDefault() ensures the relationship always returns a model instance
        $this->assertInstanceOf(BlogCategory::class, $category->parent);
    }

    public function test_posts_relationship_returns_associated_posts(): void
    {
        $category = BlogCategory::factory()->create();
        $posts = BlogPost::factory()->count(2)->create();
        $category->posts()->attach($posts);

        $this->assertCount(2, $category->fresh()->posts);
    }

    public function test_posts_returns_empty_collection_when_none_attached(): void
    {
        $category = BlogCategory::factory()->create();

        $this->assertCount(0, $category->posts);
    }
}
