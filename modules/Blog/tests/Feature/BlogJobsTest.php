<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Jobs\PublishScheduledPostsJob;
use Modules\Blog\Jobs\SendBlogPostNewsletterJob;
use Modules\Blog\Models\BlogPost;
use Tests\TestCase;

class BlogJobsTest extends TestCase
{
    use RefreshDatabase;

    // ========== PublishScheduledPostsJob ==========

    public function test_publish_scheduled_posts_job_publishes_due_posts(): void
    {
        $post = BlogPost::factory()->draft()->create([
            'publish_at' => now()->subMinutes(5),
        ]);

        (new PublishScheduledPostsJob)->handle();

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => PostStatus::Published->value,
        ]);
    }

    public function test_publish_scheduled_posts_job_ignores_future_posts(): void
    {
        $post = BlogPost::factory()->draft()->create([
            'publish_at' => now()->addHour(),
        ]);

        (new PublishScheduledPostsJob)->handle();

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => PostStatus::Draft->value,
        ]);
    }

    public function test_publish_scheduled_posts_job_dispatches_published_event(): void
    {
        Event::fake([BlogPostPublished::class]);

        $post = BlogPost::factory()->draft()->create([
            'publish_at' => now()->subMinutes(5),
        ]);

        (new PublishScheduledPostsJob)->handle();

        Event::assertDispatched(BlogPostPublished::class, function (BlogPostPublished $event) use ($post) {
            return $event->post->id === $post->id;
        });
    }

    // ========== SendBlogPostNewsletterJob ==========

    public function test_send_newsletter_job_marks_post_as_sent(): void
    {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        config([
            'services.mailrelay.api_key' => 'test-api-key',
            'services.mailrelay.host' => 'test.mailrelay.com',
        ]);

        $post = BlogPost::factory()->published()->create([
            'send_newsletter' => true,
            'newsletter_sent_at' => null,
        ]);

        (new SendBlogPostNewsletterJob($post->id))->handle();

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
        ]);

        $post->refresh();
        $this->assertNotNull($post->newsletter_sent_at);
    }
}
