<?php

namespace Modules\Social\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Jobs\PublishPostJob;
use Modules\Social\Models\Post;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:publish-scheduled
                            {--limit=50 : Maximum number of posts to process}
                            {--dry-run : Simulate without actually publishing}';

    /**
     * The console command description.
     */
    protected $description = 'Publish scheduled social media posts that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No posts will be actually published');
            $this->newLine();
        }

        $this->info('🔍 Searching for scheduled posts due for publishing...');

        // Find posts ready to publish
        $posts = Post::where('status', PostStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->with(['socialAccount', 'campaign'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('✅ No posts found ready to publish.');

            return Command::SUCCESS;
        }

        $this->info("📋 Found {$posts->count()} post(s) ready to publish.");
        $this->newLine();

        $published = 0;
        $failed = 0;

        foreach ($posts as $post) {
            $networkName = $post->socialAccount->network->value ?? 'unknown';
            $accountName = $post->socialAccount->name ?? 'Unknown';
            $scheduledDate = $post->scheduled_at->format('Y-m-d H:i:s');

            $this->line("  • Post #{$post->id} → ".ucfirst($networkName)." (@{$accountName})");
            $this->line("    Scheduled: {$scheduledDate}");

            if ($dryRun) {
                $this->line('    <fg=yellow>[DRY RUN]</> Would dispatch PublishPostJob');
                $published++;
            } else {
                try {
                    // Dispatch job to queue
                    PublishPostJob::dispatch($post);

                    $this->line('    <fg=green>✅ Job dispatched</>');
                    $published++;

                    Log::info("Dispatched PublishPostJob for post {$post->id}", [
                        'post_id' => $post->id,
                        'network' => $networkName,
                        'scheduled_at' => $scheduledDate,
                    ]);
                } catch (\Exception $e) {
                    $this->line('    <fg=red>❌ Failed: '.$e->getMessage().'</>');
                    $failed++;

                    Log::error("Failed to dispatch PublishPostJob for post {$post->id}", [
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
        }

        // Summary table
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Posts Found', $posts->count()],
                ['Published', $published],
                ['Failed', $failed],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY RUN. No posts were actually published.');
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
