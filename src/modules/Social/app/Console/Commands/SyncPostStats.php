<?php

namespace Modules\Social\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Enums\SocialNetwork;
use Modules\Social\Models\Post;

class SyncPostStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:sync-stats
                            {--days=7 : Sync posts from the last N days}
                            {--limit=100 : Maximum number of posts to process}
                            {--network= : Only sync specific network (facebook, instagram, twitter, linkedin)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync social media post statistics from platform APIs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $network = $this->option('network');

        $this->info("🔄 Syncing post stats from the last {$days} days...");

        // Build query
        $query = Post::where('status', PostStatus::PUBLISHED)
            ->whereNotNull('external_id')
            ->where('published_at', '>=', now()->subDays($days))
            ->with(['socialAccount']);

        // Filter by network if specified
        if ($network) {
            $query->whereHas('socialAccount', function ($q) use ($network) {
                $q->where('network', $network);
            });

            $this->line("Filtering by network: {$network}");
        }

        $posts = $query->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('✅ No posts found to sync.');

            return Command::SUCCESS;
        }

        $this->info("📋 Found {$posts->count()} post(s) to sync.");
        $this->newLine();

        $synced = 0;
        $failed = 0;

        foreach ($posts as $post) {
            $networkName = $post->socialAccount->network->value ?? 'unknown';
            $accountName = $post->socialAccount->name ?? 'Unknown';

            $this->line("  • Post #{$post->id} → ".ucfirst($networkName)." (@{$accountName})");

            try {
                $stats = $this->fetchStats($post);

                if ($stats) {
                    // Update post with new stats
                    $post->update([
                        'likes_count' => $stats['likes'] ?? $post->likes_count,
                        'comments_count' => $stats['comments'] ?? $post->comments_count,
                        'shares_count' => $stats['shares'] ?? $post->shares_count,
                        'reach' => $stats['reach'] ?? $post->reach,
                        'impressions' => $stats['impressions'] ?? $post->impressions,
                    ]);

                    $likesText = $stats['likes'] ?? 0;
                    $commentsText = $stats['comments'] ?? 0;
                    $sharesText = $stats['shares'] ?? 0;

                    $this->line("    <fg=green>✅ Synced:</> {$likesText} likes, {$commentsText} comments, {$sharesText} shares");
                    $synced++;

                    Log::info("Synced stats for post {$post->id}", [
                        'post_id' => $post->id,
                        'stats' => $stats,
                    ]);
                } else {
                    $this->line('    <fg=yellow>⚠️  No stats returned from API</>');
                }
            } catch (Exception $e) {
                $this->line('    <fg=red>❌ Failed: '.$e->getMessage().'</>');
                $failed++;

                Log::error("Failed to sync stats for post {$post->id}", [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        // Summary table
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Posts Found', $posts->count()],
                ['Synced', $synced],
                ['Failed', $failed],
            ]
        );

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Fetch stats from social network API
     */
    protected function fetchStats(Post $post): ?array
    {
        $network = $post->socialAccount->network;

        return match ($network) {
            SocialNetwork::FACEBOOK => $this->fetchFacebookStats($post),
            SocialNetwork::INSTAGRAM => $this->fetchInstagramStats($post),
            SocialNetwork::TWITTER => $this->fetchTwitterStats($post),
            SocialNetwork::LINKEDIN => $this->fetchLinkedInStats($post),
            default => null,
        };
    }

    /**
     * Fetch Facebook post stats
     */
    protected function fetchFacebookStats(Post $post): ?array
    {
        try {
            $accessToken = decrypt($post->socialAccount->access_token);
            $version = 'v21.0';

            $response = Http::get("https://graph.facebook.com/{$version}/{$post->external_id}", [
                'fields' => 'likes.summary(true),comments.summary(true),shares',
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                throw new Exception($response->json()['error']['message'] ?? 'API request failed');
            }

            $data = $response->json();

            return [
                'likes' => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'shares' => $data['shares']['count'] ?? 0,
                'reach' => null, // Requires insights endpoint
                'impressions' => null,
            ];
        } catch (Exception $e) {
            throw new Exception("Facebook API error: {$e->getMessage()}");
        }
    }

    /**
     * Fetch Instagram post stats
     */
    protected function fetchInstagramStats(Post $post): ?array
    {
        try {
            $accessToken = decrypt($post->socialAccount->access_token);
            $version = 'v21.0';

            $response = Http::get("https://graph.facebook.com/{$version}/{$post->external_id}", [
                'fields' => 'like_count,comments_count,insights.metric(impressions,reach)',
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                throw new Exception($response->json()['error']['message'] ?? 'API request failed');
            }

            $data = $response->json();
            $insights = $data['insights']['data'] ?? [];

            $reach = null;
            $impressions = null;

            foreach ($insights as $insight) {
                if ($insight['name'] === 'reach') {
                    $reach = $insight['values'][0]['value'] ?? null;
                }
                if ($insight['name'] === 'impressions') {
                    $impressions = $insight['values'][0]['value'] ?? null;
                }
            }

            return [
                'likes' => $data['like_count'] ?? 0,
                'comments' => $data['comments_count'] ?? 0,
                'shares' => 0, // Instagram doesn't provide shares
                'reach' => $reach,
                'impressions' => $impressions,
            ];
        } catch (Exception $e) {
            throw new Exception("Instagram API error: {$e->getMessage()}");
        }
    }

    /**
     * Fetch Twitter post stats
     */
    protected function fetchTwitterStats(Post $post): ?array
    {
        try {
            $accessToken = decrypt($post->socialAccount->access_token);

            $response = Http::withToken($accessToken)
                ->get("https://api.twitter.com/2/tweets/{$post->external_id}", [
                    'tweet.fields' => 'public_metrics',
                ]);

            if ($response->failed()) {
                throw new Exception($response->json()['errors'][0]['message'] ?? 'API request failed');
            }

            $data = $response->json();
            $metrics = $data['data']['public_metrics'] ?? [];

            return [
                'likes' => $metrics['like_count'] ?? 0,
                'comments' => $metrics['reply_count'] ?? 0,
                'shares' => $metrics['retweet_count'] ?? 0,
                'reach' => $metrics['impression_count'] ?? null,
                'impressions' => $metrics['impression_count'] ?? null,
            ];
        } catch (Exception $e) {
            throw new Exception("Twitter API error: {$e->getMessage()}");
        }
    }

    /**
     * Fetch LinkedIn post stats
     */
    protected function fetchLinkedInStats(Post $post): ?array
    {
        try {
            $accessToken = decrypt($post->socialAccount->access_token);

            $response = Http::withToken($accessToken)
                ->get("https://api.linkedin.com/v2/socialActions/{$post->external_id}");

            if ($response->failed()) {
                throw new Exception('LinkedIn API request failed');
            }

            $data = $response->json();

            return [
                'likes' => $data['likesSummary']['totalLikes'] ?? 0,
                'comments' => $data['commentsSummary']['totalComments'] ?? 0,
                'shares' => $data['sharesSummary']['totalShares'] ?? 0,
                'reach' => null, // Requires analytics API
                'impressions' => null,
            ];
        } catch (Exception $e) {
            throw new Exception("LinkedIn API error: {$e->getMessage()}");
        }
    }
}
