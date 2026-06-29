<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Tests\TestCase;

class BlogApiControllerTest extends TestCase
{
    use RefreshDatabase;

    // ========== INDEX ==========

    public function test_api_lists_published_posts(): void
    {
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->draft()->count(2)->create();

        $response = $this->getJson(route('api.blog.posts.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug']],
                'meta',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_api_does_not_list_draft_posts(): void
    {
        BlogPost::factory()->draft()->count(3)->create();

        $response = $this->getJson(route('api.blog.posts.index'));

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    // ========== SHOW ==========

    public function test_api_shows_single_post_by_slug(): void
    {
        $post = BlogPost::factory()->published()->create();

        $response = $this->getJson(route('api.blog.posts.show', $post->slug));

        $response->assertOk()
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonPath('data.title', $post->title);
    }

    public function test_api_returns_404_for_draft_post(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $response = $this->getJson(route('api.blog.posts.show', $post->slug));

        $response->assertNotFound();
    }

    public function test_api_returns_404_for_nonexistent_post(): void
    {
        $response = $this->getJson(route('api.blog.posts.show', 'slug-que-no-existe'));

        $response->assertNotFound();
    }

    // ========== CATEGORIES ==========

    public function test_api_lists_categories(): void
    {
        BlogCategory::factory()->count(3)->create();
        BlogCategory::factory()->draft()->count(2)->create();

        $response = $this->getJson(route('api.blog.categories.index'));

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);

        $this->assertCount(3, $response->json('data'));
    }

    // ========== TAGS ==========

    public function test_api_lists_tags(): void
    {
        BlogTag::factory()->count(4)->create();

        $response = $this->getJson(route('api.blog.tags.index'));

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);

        $this->assertCount(4, $response->json('data'));
    }

    // ========== SEARCH ==========

    public function test_api_search_returns_matching_posts(): void
    {
        $post = BlogPost::factory()->published()->create(['title' => 'Tutorial de Laravel']);
        BlogPost::factory()->published()->create(['title' => 'Otro articulo sin relacion']);

        $response = $this->getJson(route('api.blog.search', ['q' => 'Laravel']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($post->slug, $response->json('data.0.slug'));
    }

    public function test_api_search_returns_empty_for_no_match(): void
    {
        BlogPost::factory()->published()->count(2)->create();

        $response = $this->getJson(route('api.blog.search', ['q' => 'terminoquenoaparece12345']));

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
