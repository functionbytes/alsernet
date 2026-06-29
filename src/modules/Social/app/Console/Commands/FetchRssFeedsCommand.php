<?php

namespace Modules\Social\Console\Commands;

use Illuminate\Console\Command;
use Modules\Social\Jobs\FetchRssFeedJob;
use Modules\Social\Models\RssFeed;

class FetchRssFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:fetch-rss-feeds
                            {--feed= : Specific feed ID to fetch}
                            {--force : Force fetch all feeds regardless of schedule}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch RSS feeds and create posts from new entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $feedId = $this->option('feed');
        $force = $this->option('force');

        if ($feedId) {
            // Fetch specific feed
            $feed = RssFeed::find($feedId);

            if (! $feed) {
                $this->error("Feed with ID {$feedId} not found");

                return self::FAILURE;
            }

            $this->info("Fetching feed: {$feed->name}");
            FetchRssFeedJob::dispatch($feed);

            $this->info('Feed queued for processing');

            return self::SUCCESS;
        }

        // Fetch all due feeds
        $query = RssFeed::active();

        if (! $force) {
            $query->dueForFetch();
        }

        $feeds = $query->get();

        if ($feeds->isEmpty()) {
            $this->info('No feeds due for fetching');

            return self::SUCCESS;
        }

        $this->info("Found {$feeds->count()} feed(s) to process");

        $progressBar = $this->output->createProgressBar($feeds->count());
        $progressBar->start();

        foreach ($feeds as $feed) {
            FetchRssFeedJob::dispatch($feed);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Queued {$feeds->count()} feed(s) for processing");

        return self::SUCCESS;
    }
}
