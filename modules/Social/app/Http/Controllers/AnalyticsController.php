<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;

class AnalyticsController extends Controller
{
    public function index()
    {
        $accountId = auth()->user()->account_id;

        // Overview stats
        $totalPosts = Post::where('account_id', $accountId)->count();
        $publishedPosts = Post::where('account_id', $accountId)->where('status', 'published')->count();
        $scheduledPosts = Post::where('account_id', $accountId)->where('status', 'scheduled')->count();

        $totalEngagement = Post::where('account_id', $accountId)
            ->selectRaw('SUM(likes_count + comments_count + shares_count) as total')
            ->value('total') ?? 0;

        // Growth calculations (last 30 days vs previous 30 days)
        $currentPeriod = now()->subDays(30);
        $previousPeriod = now()->subDays(60);

        $currentPosts = Post::where('account_id', $accountId)
            ->where('created_at', '>=', $currentPeriod)
            ->count();
        $previousPosts = Post::where('account_id', $accountId)
            ->whereBetween('created_at', [$previousPeriod, $currentPeriod])
            ->count();
        $postsGrowth = $previousPosts > 0 ? round((($currentPosts - $previousPosts) / $previousPosts) * 100, 1) : 0;

        $currentEngagement = Post::where('account_id', $accountId)
            ->where('created_at', '>=', $currentPeriod)
            ->selectRaw('SUM(likes_count + comments_count + shares_count) as total')
            ->value('total') ?? 0;
        $previousEngagement = Post::where('account_id', $accountId)
            ->whereBetween('created_at', [$previousPeriod, $currentPeriod])
            ->selectRaw('SUM(likes_count + comments_count + shares_count) as total')
            ->value('total') ?? 0;
        $engagementGrowth = $previousEngagement > 0 ? round((($currentEngagement - $previousEngagement) / $previousEngagement) * 100, 1) : 0;

        // Engagement rate (average)
        $engagementRate = $publishedPosts > 0 ? round(($totalEngagement / $publishedPosts), 1) : 0;

        // Best post engagement
        $bestPost = Post::where('account_id', $accountId)
            ->where('status', 'published')
            ->orderByRaw('(likes_count + comments_count + shares_count) DESC')
            ->first();
        $bestPostEngagement = $bestPost ? ($bestPost->likes_count + $bestPost->comments_count + $bestPost->shares_count) : 0;

        // Posts by network
        $postsByNetwork = SocialAccount::where('account_id', $accountId)
            ->withCount('posts')
            ->get();

        // Network engagement data for Chart.js
        $networkEngagementData = [
            'labels' => $postsByNetwork->pluck('name')->toArray(),
            'data' => $postsByNetwork->map(function ($account) {
                return Post::where('social_account_id', $account->id)
                    ->selectRaw('SUM(likes_count + comments_count + shares_count) as total')
                    ->value('total') ?? 0;
            })->toArray(),
        ];

        // Post type distribution data for Chart.js
        $postTypes = Post::where('account_id', $accountId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();
        $postTypeData = [
            'labels' => $postTypes->map(fn ($item) => $item->type?->label() ?? 'Estándar')->toArray(),
            'data' => $postTypes->pluck('count')->toArray(),
        ];

        // Top performing posts
        $topPosts = Post::where('account_id', $accountId)
            ->where('status', 'published')
            ->orderByRaw('(likes_count + comments_count + shares_count) DESC')
            ->limit(10)
            ->get();

        // Campaign performance with stats
        $campaignStats = Campaign::where('account_id', $accountId)
            ->withCount('posts')
            ->with(['posts' => function ($query) {
                $query->selectRaw('campaign_id, SUM(likes_count) as total_likes, SUM(comments_count) as total_comments, SUM(shares_count) as total_shares')
                    ->groupBy('campaign_id');
            }])
            ->get()
            ->map(function ($campaign) {
                $totals = $campaign->posts->first();

                return (object) [
                    'campaign' => $campaign,
                    'posts_count' => $campaign->posts_count,
                    'total_likes' => $totals->total_likes ?? 0,
                    'total_comments' => $totals->total_comments ?? 0,
                    'total_shares' => $totals->total_shares ?? 0,
                ];
            });

        // Best time to post (analyze historical data)
        $bestPostingTimes = $this->calculateBestPostingTimes($accountId);

        return view('social::analytics.index', compact(
            'totalPosts',
            'publishedPosts',
            'scheduledPosts',
            'totalEngagement',
            'postsGrowth',
            'engagementGrowth',
            'engagementRate',
            'bestPostEngagement',
            'postsByNetwork',
            'networkEngagementData',
            'postTypeData',
            'topPosts',
            'campaignStats',
            'bestPostingTimes'
        ));
    }

    protected function calculateBestPostingTimes($accountId)
    {
        $posts = Post::where('account_id', $accountId)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->get();

        $hourlyEngagement = [];

        foreach ($posts as $post) {
            $hour = $post->published_at->format('H');
            $engagement = $post->likes_count + $post->comments_count + $post->shares_count;

            if (! isset($hourlyEngagement[$hour])) {
                $hourlyEngagement[$hour] = ['total' => 0, 'count' => 0];
            }

            $hourlyEngagement[$hour]['total'] += $engagement;
            $hourlyEngagement[$hour]['count']++;
        }

        $averages = [];
        foreach ($hourlyEngagement as $hour => $data) {
            $averages[$hour] = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
        }

        arsort($averages);

        return array_slice($averages, 0, 3, true);
    }
}
