<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Post;
use Modules\Social\Models\RssFeed;
use Modules\Social\Services\RssFeedParser;

class FetchRssFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 300, 900];

    public function __construct(public RssFeed $rssFeed) {}

    public function handle(): void
    {
        try {
            $parser = new RssFeedParser;
            $items = $parser->parse($this->rssFeed->feed_url);

            // Get existing posts from this feed to avoid duplicates
            $existingLinks = Post::where('account_id', $this->rssFeed->account_id)
                ->whereNotNull('external_id')
                ->pluck('external_id')
                ->toArray();

            $newPostsCount = 0;

            foreach ($items as $item) {
                // Skip if we've already imported this item
                if (in_array($item['link'] ?? '', $existingLinks)) {
                    continue;
                }

                // Generate post content from template
                $content = $this->rssFeed->generatePostContent($item);

                // Create the post
                Post::create([
                    'account_id' => $this->rssFeed->account_id,
                    'created_by' => $this->rssFeed->user_id,
                    'social_account_id' => $this->rssFeed->social_account_id,
                    'content' => $content,
                    'type' => 'link',
                    'status' => $this->rssFeed->auto_publish ? 'published' : 'draft',
                    'external_id' => $item['link'] ?? null,
                    'published_at' => $this->rssFeed->auto_publish ? now() : null,
                ]);

                $newPostsCount++;
            }

            // Update feed metadata
            $this->rssFeed->update([
                'last_fetched_at' => now(),
                'next_fetch_at' => now()->addMinutes($this->rssFeed->fetch_interval),
            ]);

            \Log::info("RSS Feed {$this->rssFeed->id}: Imported {$newPostsCount} new posts");
        } catch (\Exception $e) {
            \Log::error("RSS Feed {$this->rssFeed->id} fetch failed: {$e->getMessage()}");

            // Schedule next fetch anyway
            $this->rssFeed->update([
                'next_fetch_at' => now()->addMinutes($this->rssFeed->fetch_interval),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("RSS Feed {$this->rssFeed->id} fetch failed permanently", [
            'error' => $exception->getMessage(),
        ]);
    }
}
