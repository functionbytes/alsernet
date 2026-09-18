<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialCompetitor;
use Modules\HelpdeskSocial\Models\SocialCompetitorMetric;

class SyncCompetitorMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        public readonly ?int $competitorId = null,
    ) {
        $this->onQueue(config('helpdesksocial.queues.analytics', 'helpdesk-social-analytics'));
    }

    public function handle(): void
    {
        $competitors = SocialCompetitor::query()
            ->when($this->competitorId, function ($query) {
                $query->where('id', $this->competitorId);
            })
            ->where('is_active', true)
            ->get();

        if ($competitors->isEmpty()) {
            Log::info('SyncCompetitorMetricsJob: No active competitors found', [
                'competitor_id' => $this->competitorId,
            ]);

            return;
        }

        foreach ($competitors as $competitor) {
            try {
                $this->syncMetricsForCompetitor($competitor);
            } catch (\Throwable $e) {
                Log::error('SyncCompetitorMetricsJob: Failed to sync competitor', [
                    'competitor_id' => $competitor->id,
                    'name' => $competitor->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncMetricsForCompetitor(SocialCompetitor $competitor): void
    {
        Log::info('SyncCompetitorMetricsJob: Fetching metrics (simulated)', [
            'competitor_id' => $competitor->id,
            'name' => $competitor->name,
            'platform' => $competitor->platform,
        ]);

        $metrics = $this->generateSimulatedMetrics($competitor);

        foreach ($metrics as $metric) {
            SocialCompetitorMetric::create([
                'social_competitor_id' => $competitor->id,
                'metric_type' => $metric['type'],
                'value' => $metric['value'],
                'captured_at' => now(),
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateSimulatedMetrics(SocialCompetitor $competitor): array
    {
        // Seed random generator with competitor ID for deterministic but varied results
        mt_srand($competitor->id + (int) now()->format('Ymd'));

        $baseFollowers = mt_rand(1000, 500000);
        $engagementRate = mt_rand(10, 500) / 10000;
        $postsPerDay = mt_rand(1, 10);
        $avgLikes = (int) ($baseFollowers * $engagementRate * mt_rand(5, 15) / 10);
        $avgComments = (int) ($avgLikes * mt_rand(5, 20) / 100);
        $sentimentScore = mt_rand(30, 90) / 100;
        $responseRate = mt_rand(20, 95) / 100;
        $responseTimeMinutes = mt_rand(5, 480);

        mt_srand();

        return [
            ['type' => 'followers', 'value' => $baseFollowers],
            ['type' => 'engagement_rate', 'value' => $engagementRate],
            ['type' => 'posts_per_day', 'value' => $postsPerDay],
            ['type' => 'avg_likes', 'value' => $avgLikes],
            ['type' => 'avg_comments', 'value' => $avgComments],
            ['type' => 'sentiment_score', 'value' => $sentimentScore],
            ['type' => 'response_rate', 'value' => $responseRate],
            ['type' => 'response_time_minutes', 'value' => $responseTimeMinutes],
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncCompetitorMetricsJob failed permanently', [
            'competitor_id' => $this->competitorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
