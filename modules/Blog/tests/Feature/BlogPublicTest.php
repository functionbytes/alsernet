<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Tests\TestCase;

class BlogPublicTest extends TestCase
{
    use RefreshDatabase;

    // ========== PUBLIC LISTING ==========

    public function test_blog_post_list_is_accessible(): void
    {
        BlogPost::factory()->published()->count(3)->create();

        $response = $this->get(route('blog.public.index'));

        $response->assertOk();
    }

    public function test_blog_list_shows_only_published_posts(): void
    {
        $published = BlogPost::factory()->published()->create();
        BlogPost::factory()->draft()->create();

        $response = $this->get(route('blog.public.index'));

        $response->assertOk();
        $response->assertViewHas('posts', function ($posts) use ($published) {
            return $posts->contains('id', $published->id);
        });
    }

    // ========== SINGLE POST ==========

    public function test_published_post_is_accessible(): void
    {
        $post = BlogPost::factory()->published()->create(['slug' => 'my-published-post']);

        $response = $this->get(route('blog.public.post', $post->slug));

        $response->assertOk();
    }

    public function test_draft_post_returns_404(): void
    {
        $post = BlogPost::factory()->draft()->create(['slug' => 'my-draft-post']);

        $response = $this->get(route('blog.public.post', $post->slug));

        $response->assertNotFound();
    }

    public function test_pending_post_returns_404(): void
    {
        $post = BlogPost::factory()->pending()->create(['slug' => 'my-pending-post']);

        $response = $this->get(route('blog.public.post', $post->slug));

        $response->assertNotFound();
    }

    public function test_nonexistent_post_returns_404(): void
    {
        $response = $this->get(route('blog.public.post', 'slug-does-not-exist'));

        $response->assertNotFound();
    }

    // ========== CATEGORY ==========

    public function test_blog_category_page_is_accessible(): void
    {
        $category = BlogCategory::factory()->create(['slug' => 'tech-category', 'status' => 'published']);

        $response = $this->get(route('blog.public.category', $category->slug));

        $response->assertOk();
    }

    public function test_nonexistent_category_returns_404(): void
    {
        $response = $this->get(route('blog.public.category', 'slug-does-not-exist'));

        $response->assertNotFound();
    }

    // ========== COMMENTS ==========

    public function test_comment_requires_name_email_content(): void
    {
        $post = BlogPost::factory()->published()->create(['slug' => 'test-post']);

        $response = $this->postJson(route('blog.public.comments.store', $post->slug), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['author_name', 'author_email', 'content']);
    }

    public function test_comment_creation_when_comments_disabled_returns_403(): void
    {
        config(['blog.allow_comments' => false]);

        $post = BlogPost::factory()->published()->create(['slug' => 'test-post-comments']);

        $response = $this->postJson(route('blog.public.comments.store', $post->slug), [
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a test comment with enough length.',
        ]);

        $response->assertStatus(403);
    }

    public function test_comment_can_be_submitted_when_comments_enabled(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'commentable-post']);

        $response = $this->postJson(route('blog.public.comments.store', $post->slug), [
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'content' => 'This is a test comment that is long enough.',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('blog_post_comments', [
            'blog_post_id' => $post->id,
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
        ]);
    }

    public function test_comment_on_draft_post_returns_404(): void
    {
        $post = BlogPost::factory()->draft()->create(['slug' => 'draft-no-comment']);

        $response = $this->postJson(route('blog.public.comments.store', $post->slug), [
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'content' => 'Should not be allowed.',
        ]);

        $response->assertNotFound();
    }

    public function test_honeypot_website_field_fails_validation(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'honeypot-test']);

        // The website field has max:0 — any non-empty value fails validation (bot trap)
        $response = $this->postJson(route('blog.public.comments.store', $post->slug), [
            'author_name' => 'Bot',
            'author_email' => 'bot@spam.com',
            'content' => 'Spam content that is long enough.',
            'website' => 'http://spam.com',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('blog_post_comments', [
            'blog_post_id' => $post->id,
            'author_email' => 'bot@spam.com',
        ]);
    }
}
