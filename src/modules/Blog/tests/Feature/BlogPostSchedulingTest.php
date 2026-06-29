<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Jobs\PublishScheduledPostsJob;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Tests\TestCase;

class BlogPostSchedulingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Scheduled publishing
    // -------------------------------------------------------------------------

    public function test_post_with_publish_at_is_not_immediately_published(): void
    {
        $post = BlogPost::factory()->draft()->create([
            'publish_at' => now()->addHours(2),
        ]);

        $this->assertEquals(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_publish_scheduled_job_publishes_due_posts(): void
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

    public function test_publish_scheduled_job_ignores_future_posts(): void
    {
        $post = BlogPost::factory()->draft()->create([
            'publish_at' => now()->addHours(1),
        ]);

        (new PublishScheduledPostsJob)->handle();

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'status' => PostStatus::Draft->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // View count — IP deduplication via cache
    // -------------------------------------------------------------------------

    public function test_published_post_increments_view_count_once_per_ip(): void
    {
        $post = BlogPost::factory()->published()->create(['views' => 0]);
        $ip = request()->ip();

        // Ensure the cache key is clear before calling
        Cache::forget("blog_post_view:{$post->id}:{$ip}");

        $post->incrementViewCount();

        $this->assertEquals(1, $post->fresh()->views);
    }

    public function test_view_count_not_incremented_twice_from_same_ip(): void
    {
        $post = BlogPost::factory()->published()->create(['views' => 0]);
        $ip = request()->ip();

        // Pre-populate the cache key as if the view was already counted for this IP
        Cache::put("blog_post_view:{$post->id}:{$ip}", true, now()->addHours(24));

        $post->incrementViewCount();

        // Count should stay at 0 because the cache guard already fired
        $this->assertEquals(0, $post->fresh()->views);
    }
}
