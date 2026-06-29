<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Blog\Enums\CommentStatus;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostComment;
use Tests\TestCase;

class BlogCommentPublicTest extends TestCase
{
    use RefreshDatabase;

    private function validCommentPayload(array $overrides = []): array
    {
        return array_merge([
            'author_name' => 'Ana García',
            'author_email' => 'ana@example.com',
            'content' => 'Este es un comentario de prueba con suficiente longitud para pasar la validación.',
        ], $overrides);
    }

    // ========== GUEST SUBMISSION ==========

    public function test_guest_can_submit_comment_when_comments_are_enabled(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'commentable-post']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload()
        );

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('blog_post_comments', [
            'blog_post_id' => $post->id,
            'author_email' => 'ana@example.com',
        ]);
    }

    public function test_guest_cannot_submit_comment_when_comments_are_disabled(): void
    {
        config(['blog.allow_comments' => false]);

        $post = BlogPost::factory()->published()->create(['slug' => 'no-comments-post']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload()
        );

        $response->assertForbidden();
    }

    // ========== RATE LIMITING ==========

    public function test_rate_limit_blocks_after_five_comment_submissions(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'throttle-test-post']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(
                route('blog.public.comments.store', $post->slug),
                $this->validCommentPayload(['author_email' => "user{$i}@example.com"])
            );
        }

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload(['author_email' => 'extra@example.com'])
        );

        $response->assertStatus(429);
    }

    // ========== REQUIRED FIELDS VALIDATION ==========

    public function test_comment_requires_author_name(): void
    {
        $post = BlogPost::factory()->published()->create(['slug' => 'validation-post-name']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload(['author_name' => ''])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['author_name']);
    }

    public function test_comment_requires_valid_email(): void
    {
        $post = BlogPost::factory()->published()->create(['slug' => 'validation-post-email']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload(['author_email' => 'not-an-email'])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['author_email']);
    }

    public function test_comment_content_must_meet_minimum_length(): void
    {
        $post = BlogPost::factory()->published()->create(['slug' => 'validation-post-content']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload(['content' => 'Short'])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_comment_on_nonexistent_post_returns_404(): void
    {
        $response = $this->postJson(
            route('blog.public.comments.store', 'nonexistent-slug'),
            $this->validCommentPayload()
        );

        $response->assertNotFound();
    }

    // ========== XSS SANITIZATION ==========

    public function test_html_tags_are_stripped_from_comment_content(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'xss-test-post']);

        $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload([
                'content' => '<script>alert("xss")</script>This is a legitimate comment long enough.',
            ])
        );

        $stored = BlogPostComment::where('blog_post_id', $post->id)->first();

        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('<script>', $stored->content);
        $this->assertStringNotContainsString('alert', $stored->content);
    }

    // ========== HONEYPOT ==========

    public function test_honeypot_website_field_causes_validation_failure(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'honeypot-post']);

        $response = $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload(['website' => 'http://spam.com'])
        );

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('blog_post_comments', [
            'blog_post_id' => $post->id,
            'author_email' => 'ana@example.com',
        ]);
    }

    // ========== EVENT DISPATCHED ==========

    public function test_blog_comment_created_event_is_dispatched_on_submission(): void
    {
        config(['blog.allow_comments' => true]);

        Event::fake([BlogCommentCreated::class]);

        $post = BlogPost::factory()->published()->create(['slug' => 'event-dispatch-post']);

        $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload()
        );

        Event::assertDispatched(BlogCommentCreated::class);
    }

    public function test_comment_is_stored_with_pending_status(): void
    {
        config(['blog.allow_comments' => true]);

        $post = BlogPost::factory()->published()->create(['slug' => 'pending-status-post']);

        $this->postJson(
            route('blog.public.comments.store', $post->slug),
            $this->validCommentPayload()
        );

        $this->assertDatabaseHas('blog_post_comments', [
            'blog_post_id' => $post->id,
            'status' => CommentStatus::Pending->value,
        ]);
    }
}
