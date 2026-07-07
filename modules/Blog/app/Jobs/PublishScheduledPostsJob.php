<?php

namespace Modules\Blog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Models\BlogPost;

class PublishScheduledPostsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function handle(): void
    {
        $published = 0;

        Log::info('PublishScheduledPostsJob: iniciando publicacion de posts programados.');

        BlogPost::query()
            ->where('status', PostStatus::Draft)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->chunkById(100, function ($posts) use (&$published): void {
                foreach ($posts as $post) {
                    try {
                        $post->update([
                            'status' => PostStatus::Published,
                            'published_at' => now(),
                            'publish_at' => null,
                        ]);

                        BlogPostPublished::dispatch($post);

                        $published++;
                    } catch (\Throwable $e) {
                        Log::error('PublishScheduledPostsJob: error al publicar post ID '.$post->id.'.', [
                            'exception' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info("PublishScheduledPostsJob: finalizado. Posts publicados: {$published}.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishScheduledPostsJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
