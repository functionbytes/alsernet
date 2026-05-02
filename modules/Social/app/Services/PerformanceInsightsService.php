<?php

namespace Modules\Social\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Social\Models\Post;

class PerformanceInsightsService
{
    /**
     * Get comprehensive performance insights for an account
     */
    public function getInsights(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : now();

        // Get published posts in date range
        $posts = Post::where('account_id', $accountId)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->with('socialAccount')
            ->get();

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $start->diffInDays($end),
            ],
            'overview' => $this->calculateOverview($posts),
            'engagement' => $this->calculateEngagementMetrics($posts),
            'growth' => $this->calculateGrowthMetrics($accountId, $start, $end),
            'top_posts' => $this->getTopPosts($posts),
            'by_network' => $this->analyzeByNetwork($posts),
            'by_type' => $this->analyzeByType($posts),
            'by_hour' => $this->analyzeByHour($posts),
            'by_day' => $this->analyzeByDay($posts),
            'trends' => $this->calculateTrends($accountId, $start, $end),
        ];
    }

    /**
     * Calculate overview metrics
     */
    protected function calculateOverview(Collection $posts): array
    {
        $totalPosts = $posts->count();
        $totalReach = $posts->sum('reach');
        $totalImpressions = $posts->sum('impressions');
        $totalLikes = $posts->sum('likes_count');
        $totalComments = $posts->sum('comments_count');
        $totalShares = $posts->sum('shares_count');
        $totalClicks = $posts->sum('clicks');

        $totalEngagements = $totalLikes + $totalComments + $totalShares + $totalClicks;

        return [
            'total_posts' => $totalPosts,
            'total_reach' => $totalReach,
            'total_impressions' => $totalImpressions,
            'total_engagements' => $totalEngagements,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'total_shares' => $totalShares,
            'total_clicks' => $totalClicks,
            'avg_reach' => $totalPosts > 0 ? round($totalReach / $totalPosts) : 0,
            'avg_impressions' => $totalPosts > 0 ? round($totalImpressions / $totalPosts) : 0,
            'avg_engagements' => $totalPosts > 0 ? round($totalEngagements / $totalPosts) : 0,
        ];
    }

    /**
     * Calculate engagement metrics
     */
    protected function calculateEngagementMetrics(Collection $posts): array
    {
        $totalReach = $posts->sum('reach');
        $totalImpressions = $posts->sum('impressions');
        $totalEngagements = $posts->sum(function ($post) {
            return ($post->likes_count ?? 0) +
                   ($post->comments_count ?? 0) +
                   ($post->shares_count ?? 0) +
                   ($post->clicks ?? 0);
        });

        $engagementRate = $totalReach > 0
            ? round(($totalEngagements / $totalReach) * 100, 2)
            : 0;

        $impressionRate = $totalImpressions > 0
            ? round(($totalEngagements / $totalImpressions) * 100, 2)
            : 0;

        // Calculate engagement score (weighted)
        $engagementScore = $posts->avg(function ($post) {
            $likes = $post->likes_count ?? 0;
            $comments = $post->comments_count ?? 0;
            $shares = $post->shares_count ?? 0;
            $reach = $post->reach ?? 1;

            $score = ($likes * 1) + ($comments * 3) + ($shares * 5);

            return $reach > 0 ? ($score / $reach) * 100 : 0;
        });

        return [
            'engagement_rate' => $engagementRate,
            'impression_rate' => $impressionRate,
            'engagement_score' => round($engagementScore ?? 0, 2),
            'avg_likes_per_post' => round($posts->avg('likes_count') ?? 0),
            'avg_comments_per_post' => round($posts->avg('comments_count') ?? 0),
            'avg_shares_per_post' => round($posts->avg('shares_count') ?? 0),
            'avg_clicks_per_post' => round($posts->avg('clicks') ?? 0),
        ];
    }

    /**
     * Calculate growth metrics
     */
    protected function calculateGrowthMetrics(int $accountId, Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end);

        // Get previous period for comparison
        $prevStart = $start->copy()->subDays($days);
        $prevEnd = $start->copy()->subDay();

        $currentPosts = Post::where('account_id', $accountId)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->get();

        $previousPosts = Post::where('account_id', $accountId)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$prevStart, $prevEnd])
            ->get();

        $currentEngagements = $currentPosts->sum(function ($post) {
            return ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0);
        });

        $previousEngagements = $previousPosts->sum(function ($post) {
            return ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0);
        });

        $currentReach = $currentPosts->sum('reach');
        $previousReach = $previousPosts->sum('reach');

        return [
            'posts_growth' => $this->calculateGrowthRate($currentPosts->count(), $previousPosts->count()),
            'engagement_growth' => $this->calculateGrowthRate($currentEngagements, $previousEngagements),
            'reach_growth' => $this->calculateGrowthRate($currentReach, $previousReach),
            'current_period' => [
                'posts' => $currentPosts->count(),
                'engagements' => $currentEngagements,
                'reach' => $currentReach,
            ],
            'previous_period' => [
                'posts' => $previousPosts->count(),
                'engagements' => $previousEngagements,
                'reach' => $previousReach,
            ],
        ];
    }

    /**
     * Calculate growth rate percentage
     */
    protected function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get top performing posts
     */
    protected function getTopPosts(Collection $posts, int $limit = 10): array
    {
        return $posts->sortByDesc(function ($post) {
            $reach = $post->reach ?? 1;
            $engagements = ($post->likes_count ?? 0) +
                          ($post->comments_count ?? 0) +
                          ($post->shares_count ?? 0);

            return $reach > 0 ? ($engagements / $reach) * 100 : 0;
        })
            ->take($limit)
            ->values()
            ->map(function ($post) {
                $reach = $post->reach ?? 1;
                $engagements = ($post->likes_count ?? 0) +
                              ($post->comments_count ?? 0) +
                              ($post->shares_count ?? 0);

                return [
                    'id' => $post->id,
                    'content' => \Str::limit($post->content, 100),
                    'type' => $post->type,
                    'network' => $post->socialAccount->network->value ?? 'unknown',
                    'published_at' => $post->published_at?->toDateTimeString(),
                    'reach' => $post->reach,
                    'engagements' => $engagements,
                    'engagement_rate' => $reach > 0 ? round(($engagements / $reach) * 100, 2) : 0,
                    'likes' => $post->likes_count,
                    'comments' => $post->comments_count,
                    'shares' => $post->shares_count,
                ];
            })
            ->toArray();
    }

    /**
     * Analyze performance by network
     */
    protected function analyzeByNetwork(Collection $posts): array
    {
        return $posts->groupBy(fn ($post) => $post->socialAccount->network->value ?? 'unknown')
            ->map(function ($networkPosts, $network) {
                $totalReach = $networkPosts->sum('reach');
                $totalEngagements = $networkPosts->sum(function ($post) {
                    return ($post->likes_count ?? 0) +
                           ($post->comments_count ?? 0) +
                           ($post->shares_count ?? 0);
                });

                return [
                    'network' => $network,
                    'posts_count' => $networkPosts->count(),
                    'total_reach' => $totalReach,
                    'total_engagements' => $totalEngagements,
                    'avg_reach' => round($networkPosts->avg('reach') ?? 0),
                    'avg_engagements' => $networkPosts->count() > 0
                        ? round($totalEngagements / $networkPosts->count())
                        : 0,
                    'engagement_rate' => $totalReach > 0
                        ? round(($totalEngagements / $totalReach) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('engagement_rate')
            ->values()
            ->toArray();
    }

    /**
     * Analyze performance by content type
     */
    protected function analyzeByType(Collection $posts): array
    {
        return $posts->groupBy('type')
            ->map(function ($typePosts, $type) {
                $totalReach = $typePosts->sum('reach');
                $totalEngagements = $typePosts->sum(function ($post) {
                    return ($post->likes_count ?? 0) +
                           ($post->comments_count ?? 0) +
                           ($post->shares_count ?? 0);
                });

                return [
                    'type' => $type,
                    'posts_count' => $typePosts->count(),
                    'total_reach' => $totalReach,
                    'total_engagements' => $totalEngagements,
                    'avg_reach' => round($typePosts->avg('reach') ?? 0),
                    'avg_engagements' => $typePosts->count() > 0
                        ? round($totalEngagements / $typePosts->count())
                        : 0,
                    'engagement_rate' => $totalReach > 0
                        ? round(($totalEngagements / $totalReach) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('engagement_rate')
            ->values()
            ->toArray();
    }

    /**
     * Analyze performance by hour of day
     */
    protected function analyzeByHour(Collection $posts): array
    {
        $hourlyData = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'posts_count' => 0,
                'total_reach' => 0,
                'total_engagements' => 0,
            ];
        }

        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $hour = (int) $post->published_at->format('G');
            $engagements = ($post->likes_count ?? 0) +
                          ($post->comments_count ?? 0) +
                          ($post->shares_count ?? 0);

            $hourlyData[$hour]['posts_count']++;
            $hourlyData[$hour]['total_reach'] += $post->reach ?? 0;
            $hourlyData[$hour]['total_engagements'] += $engagements;
        }

        return collect($hourlyData)->map(function ($data) {
            $avgReach = $data['posts_count'] > 0
                ? round($data['total_reach'] / $data['posts_count'])
                : 0;

            $avgEngagements = $data['posts_count'] > 0
                ? round($data['total_engagements'] / $data['posts_count'])
                : 0;

            $engagementRate = $data['total_reach'] > 0
                ? round(($data['total_engagements'] / $data['total_reach']) * 100, 2)
                : 0;

            return [
                'hour' => $data['hour'],
                'posts_count' => $data['posts_count'],
                'avg_reach' => $avgReach,
                'avg_engagements' => $avgEngagements,
                'engagement_rate' => $engagementRate,
            ];
        })->toArray();
    }

    /**
     * Analyze performance by day of week
     */
    protected function analyzeByDay(Collection $posts): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayData = [];

        foreach ($days as $index => $day) {
            $dayData[$index] = [
                'day' => $day,
                'day_number' => $index + 1,
                'posts_count' => 0,
                'total_reach' => 0,
                'total_engagements' => 0,
            ];
        }

        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $dayIndex = (int) $post->published_at->dayOfWeekIso - 1;
            $engagements = ($post->likes_count ?? 0) +
                          ($post->comments_count ?? 0) +
                          ($post->shares_count ?? 0);

            $dayData[$dayIndex]['posts_count']++;
            $dayData[$dayIndex]['total_reach'] += $post->reach ?? 0;
            $dayData[$dayIndex]['total_engagements'] += $engagements;
        }

        return collect($dayData)->map(function ($data) {
            $avgReach = $data['posts_count'] > 0
                ? round($data['total_reach'] / $data['posts_count'])
                : 0;

            $avgEngagements = $data['posts_count'] > 0
                ? round($data['total_engagements'] / $data['posts_count'])
                : 0;

            $engagementRate = $data['total_reach'] > 0
                ? round(($data['total_engagements'] / $data['total_reach']) * 100, 2)
                : 0;

            return [
                'day' => $data['day'],
                'day_number' => $data['day_number'],
                'posts_count' => $data['posts_count'],
                'avg_reach' => $avgReach,
                'avg_engagements' => $avgEngagements,
                'engagement_rate' => $engagementRate,
            ];
        })->toArray();
    }

    /**
     * Calculate daily trends
     */
    protected function calculateTrends(int $accountId, Carbon $start, Carbon $end): array
    {
        $posts = Post::where('account_id', $accountId)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->orderBy('published_at')
            ->get();

        $dailyData = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dateKey = $current->toDateString();
            $dailyData[$dateKey] = [
                'date' => $dateKey,
                'posts' => 0,
                'reach' => 0,
                'engagements' => 0,
            ];
            $current->addDay();
        }

        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $dateKey = $post->published_at->toDateString();

            if (isset($dailyData[$dateKey])) {
                $dailyData[$dateKey]['posts']++;
                $dailyData[$dateKey]['reach'] += $post->reach ?? 0;
                $dailyData[$dateKey]['engagements'] += ($post->likes_count ?? 0) +
                                                       ($post->comments_count ?? 0) +
                                                       ($post->shares_count ?? 0);
            }
        }

        return array_values($dailyData);
    }

    /**
     * Export insights to CSV
     */
    public function exportToCsv(int $accountId, ?string $startDate = null, ?string $endDate = null): string
    {
        $insights = $this->getInsights($accountId, $startDate, $endDate);

        $csv = "Performance Insights Report\n";
        $csv .= "Period: {$insights['period']['start']} to {$insights['period']['end']}\n\n";

        $csv .= "Overview\n";
        $csv .= "Metric,Value\n";
        foreach ($insights['overview'] as $key => $value) {
            $csv .= "{$key},{$value}\n";
        }

        $csv .= "\nEngagement Metrics\n";
        $csv .= "Metric,Value\n";
        foreach ($insights['engagement'] as $key => $value) {
            $csv .= "{$key},{$value}\n";
        }

        $csv .= "\nTop Posts\n";
        $csv .= "Content,Type,Network,Reach,Engagements,Engagement Rate\n";
        foreach ($insights['top_posts'] as $post) {
            $csv .= "\"{$post['content']}\",{$post['type']},{$post['network']},{$post['reach']},{$post['engagements']},{$post['engagement_rate']}\n";
        }

        return $csv;
    }
}
