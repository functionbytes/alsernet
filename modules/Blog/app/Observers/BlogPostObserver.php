<?php

namespace Modules\Blog\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Models\BlogPost;

class BlogPostObserver
{
    public function created(BlogPost $post): void
    {
        if (empty($post->seo_description)) {
            $this->generateSeoDescription($post);
        }

        if ($post->status === PostStatus::Published) {
            BlogPostPublished::dispatch($post);
        }
    }

    public function deleted(BlogPost $post): void
    {
        $post->comments()->delete();
    }

    public function updated(BlogPost $post): void
    {
        if ($post->wasChanged('content') && empty($post->seo_description)) {
            $this->generateSeoDescription($post);
        }

        $previousStatus = $post->getOriginal('status');

        if (
            $post->wasChanged('status')
            && $post->status === PostStatus::Published
            && $previousStatus !== PostStatus::Published
        ) {
            BlogPostPublished::dispatch($post);
        }

        if ($post->wasChanged('status') || $post->status === PostStatus::Published) {
            $this->flushPublicCache();
        }
    }

    private function flushPublicCache(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget("blog.public.index.page.{$i}");
        }

        Cache::forget('blog.public.index.featured');
    }

    private function generateSeoDescription(BlogPost $post): void
    {
        if (! empty($post->seo_description)) {
            return;
        }

        $description = Str::limit(strip_tags($post->description ?? $post->content ?? ''), 160);

        if (filled($description)) {
            BlogPost::withoutEvents(fn () => $post->updateQuietly(['seo_description' => $description]));
        }
    }
}
