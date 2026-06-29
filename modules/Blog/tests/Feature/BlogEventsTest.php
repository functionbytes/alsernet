<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Events\CommentApproved;
use Modules\Blog\Jobs\SendBlogPostNewsletterJob;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostComment;
use Modules\Blog\Services\BlogPostService;
use Modules\Blog\Services\CommentService;
use Tests\TestCase;

class BlogEventsTest extends TestCase
{
    use RefreshDatabase;

    // ========== BlogPostPublished ==========

    public function test_blog_post_published_event_fires_when_post_is_published(): void
    {
        Event::fake([BlogPostPublished::class]);

        $post = BlogPost::factory()->draft()->create();
        $service = new BlogPostService;
        $service->publishPost($post);

        Event::assertDispatched(BlogPostPublished::class, function (BlogPostPublished $event) use ($post) {
            return $event->post->id === $post->id;
        });
    }

    public function test_send_newsletter_listener_queues_job_on_publish(): void
    {
        Queue::fake();

        $post = BlogPost::factory()->draft()->create(['send_newsletter' => true]);
        $service = new BlogPostService;
        $service->publishPost($post);

        Queue::assertPushed(SendBlogPostNewsletterJob::class, function (SendBlogPostNewsletterJob $job) use ($post) {
            return $job->postId === $post->id;
        });
    }

    // ========== BlogCommentCreated ==========

    public function test_blog_comment_created_event_fires_on_new_comment(): void
    {
        Event::fake([BlogCommentCreated::class]);

        $post = BlogPost::factory()->published()->create();
        $service = new CommentService;
        $comment = $service->submitComment($post, [
            'author_name' => 'Juan Test',
            'author_email' => 'juan@example.com',
            'content' => 'Este es un comentario de prueba valido.',
        ]);

        Event::assertDispatched(BlogCommentCreated::class, function (BlogCommentCreated $event) use ($comment) {
            return $event->comment->id === $comment->id;
        });
    }

    // ========== CommentApproved ==========

    public function test_comment_approved_event_fires_on_approval(): void
    {
        Event::fake([CommentApproved::class]);

        $post = BlogPost::factory()->published()->create();
        $service = new CommentService;

        // Create comment directly to avoid spam detection interfering
        $pendingComment = BlogPostComment::factory()
            ->for($post, 'post')
            ->pending()
            ->create();

        $service->approveComment($pendingComment);

        Event::assertDispatched(CommentApproved::class, function (CommentApproved $event) use ($pendingComment) {
            return $event->comment->id === $pendingComment->id;
        });
    }

    // ========== Spam detection ==========

    public function test_spam_comment_does_not_fire_comment_created_event(): void
    {
        Event::fake([BlogCommentCreated::class]);

        $post = BlogPost::factory()->published()->create();
        $service = new CommentService;

        // Honeypot field filled indicates spam
        $service->submitComment($post, [
            'author_name' => 'Spammer',
            'author_email' => 'spam@spam.com',
            'content' => 'Buy cheap pills now!',
            'website' => 'http://spam.com', // honeypot
        ]);

        Event::assertNotDispatched(BlogCommentCreated::class);
    }
}
