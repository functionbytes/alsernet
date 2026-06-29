<?php

namespace Modules\Social\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;

class BestTimeToPostService
{
    /**
     * Get best times to post for a specific account
     */
    public function getBestTimesForAccount(SocialAccount $account, int $days = 90): array
    {
        $posts = $this->getPublishedPosts($account, $days);

        if ($posts->count() < 10) {
            return $this->getDefaultRecommendations($account->network->value);
        }

        return [
            'best_hours' => $this->calculateBestHours($posts),
            'best_days' => $this->calculateBestDays($posts),
            'best_times' => $this->calculateBestTimes($posts),
            'engagement_by_hour' => $this->getEngagementByHour($posts),
            'engagement_by_day' => $this->getEngagementByDay($posts),
            'recommendations' => $this->generateRecommendations($posts, $account->network->value),
            'data_points' => $posts->count(),
            'period' => $days,
        ];
    }

    /**
     * Get best times to post across all accounts for a network
     */
    public function getBestTimesForNetwork(int $accountId, string $network, int $days = 90): array
    {
        $accounts = SocialAccount::where('account_id', $accountId)
            ->where('network', $network)
            ->where('status', 1)
            ->get();

        $allPosts = collect();

        foreach ($accounts as $account) {
            $allPosts = $allPosts->merge($this->getPublishedPosts($account, $days));
        }

        if ($allPosts->count() < 10) {
            return $this->getDefaultRecommendations($network);
        }

        return [
            'best_hours' => $this->calculateBestHours($allPosts),
            'best_days' => $this->calculateBestDays($allPosts),
            'best_times' => $this->calculateBestTimes($allPosts),
            'engagement_by_hour' => $this->getEngagementByHour($allPosts),
            'engagement_by_day' => $this->getEngagementByDay($allPosts),
            'recommendations' => $this->generateRecommendations($allPosts, $network),
            'data_points' => $allPosts->count(),
            'period' => $days,
            'accounts_analyzed' => $accounts->count(),
        ];
    }

    /**
     * Get best times across all networks
     */
    public function getBestTimesOverall(int $accountId, int $days = 90): array
    {
        $networks = ['facebook', 'instagram', 'twitter', 'linkedin'];
        $results = [];

        foreach ($networks as $network) {
            $data = $this->getBestTimesForNetwork($accountId, $network, $days);
            if ($data['data_points'] > 0) {
                $results[$network] = $data;
            }
        }

        return $results;
    }

    /**
     * Get published posts for analysis
     */
    protected function getPublishedPosts(SocialAccount $account, int $days): Collection
    {
        return Post::where('account_id', $account->account_id)
            ->whereHas('socialAccounts', function ($query) use ($account) {
                $query->where('social_accounts.id', $account->id);
            })
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays($days))
            ->whereNotNull('published_at')
            ->get();
    }

    /**
     * Calculate engagement score for a post
     */
    protected function getEngagementScore(Post $post): float
    {
        // Weighted engagement score
        $likes = $post->likes_count ?? 0;
        $comments = $post->comments_count ?? 0;
        $shares = $post->shares_count ?? 0;
        $reach = $post->reach ?? 1;

        // Comments and shares are weighted more heavily
        $engagementScore = ($likes * 1) + ($comments * 3) + ($shares * 5);

        // Normalize by reach to get engagement rate
        return $reach > 0 ? ($engagementScore / $reach) * 100 : 0;
    }

    /**
     * Calculate best hours to post
     */
    protected function calculateBestHours(Collection $posts): array
    {
        $hourlyData = [];

        // Initialize all hours
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'posts_count' => 0,
                'total_engagement' => 0,
                'avg_engagement' => 0,
            ];
        }

        // Aggregate data
        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $hour = $post->published_at->format('G');
            $engagement = $this->getEngagementScore($post);

            $hourlyData[$hour]['posts_count']++;
            $hourlyData[$hour]['total_engagement'] += $engagement;
        }

        // Calculate averages
        foreach ($hourlyData as $hour => $data) {
            if ($data['posts_count'] > 0) {
                $hourlyData[$hour]['avg_engagement'] = $data['total_engagement'] / $data['posts_count'];
            }
        }

        // Sort by engagement
        usort($hourlyData, function ($a, $b) {
            return $b['avg_engagement'] <=> $a['avg_engagement'];
        });

        return array_slice($hourlyData, 0, 5); // Top 5 hours
    }

    /**
     * Calculate best days to post
     */
    protected function calculateBestDays(Collection $posts): array
    {
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dailyData = [];

        // Initialize all days
        foreach ($dayNames as $index => $dayName) {
            $dailyData[$index] = [
                'day_number' => $index + 1,
                'day_name' => $dayName,
                'posts_count' => 0,
                'total_engagement' => 0,
                'avg_engagement' => 0,
            ];
        }

        // Aggregate data
        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $dayOfWeek = $post->published_at->dayOfWeek; // 0 = Sunday, 1 = Monday
            $dayIndex = $dayOfWeek === 0 ? 6 : $dayOfWeek - 1; // Convert to 0 = Monday
            $engagement = $this->getEngagementScore($post);

            $dailyData[$dayIndex]['posts_count']++;
            $dailyData[$dayIndex]['total_engagement'] += $engagement;
        }

        // Calculate averages
        foreach ($dailyData as $index => $data) {
            if ($data['posts_count'] > 0) {
                $dailyData[$index]['avg_engagement'] = $data['total_engagement'] / $data['posts_count'];
            }
        }

        // Sort by engagement
        usort($dailyData, function ($a, $b) {
            return $b['avg_engagement'] <=> $a['avg_engagement'];
        });

        return array_slice($dailyData, 0, 3); // Top 3 days
    }

    /**
     * Calculate best specific times (day + hour combinations)
     */
    protected function calculateBestTimes(Collection $posts): array
    {
        $timeData = [];

        // Aggregate data
        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $dayOfWeek = $post->published_at->dayOfWeek;
            $hour = $post->published_at->format('G');
            $key = "{$dayOfWeek}_{$hour}";

            if (! isset($timeData[$key])) {
                $timeData[$key] = [
                    'day_of_week' => $dayOfWeek,
                    'day_name' => $post->published_at->format('l'),
                    'hour' => $hour,
                    'posts_count' => 0,
                    'total_engagement' => 0,
                    'avg_engagement' => 0,
                ];
            }

            $engagement = $this->getEngagementScore($post);
            $timeData[$key]['posts_count']++;
            $timeData[$key]['total_engagement'] += $engagement;
        }

        // Calculate averages and filter (need at least 2 posts for reliability)
        $timeData = array_filter($timeData, function ($data) {
            return $data['posts_count'] >= 2;
        });

        foreach ($timeData as $key => $data) {
            $timeData[$key]['avg_engagement'] = $data['total_engagement'] / $data['posts_count'];
        }

        // Sort by engagement
        usort($timeData, function ($a, $b) {
            return $b['avg_engagement'] <=> $a['avg_engagement'];
        });

        return array_slice($timeData, 0, 10); // Top 10 times
    }

    /**
     * Get engagement distribution by hour
     */
    protected function getEngagementByHour(Collection $posts): array
    {
        $data = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $data[$hour] = 0;
        }

        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $hour = $post->published_at->format('G');
            $data[$hour] += $this->getEngagementScore($post);
        }

        return $data;
    }

    /**
     * Get engagement distribution by day of week
     */
    protected function getEngagementByDay(Collection $posts): array
    {
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $data = [];

        foreach ($dayNames as $dayName) {
            $data[$dayName] = 0;
        }

        foreach ($posts as $post) {
            if (! $post->published_at) {
                continue;
            }

            $dayOfWeek = $post->published_at->dayOfWeek;
            $dayIndex = $dayOfWeek === 0 ? 6 : $dayOfWeek - 1;
            $dayName = $dayNames[$dayIndex];

            $data[$dayName] += $this->getEngagementScore($post);
        }

        return $data;
    }

    /**
     * Generate AI recommendations based on analysis
     */
    protected function generateRecommendations(Collection $posts, string $network): array
    {
        $bestHours = $this->calculateBestHours($posts);
        $bestDays = $this->calculateBestDays($posts);
        $bestTimes = $this->calculateBestTimes($posts);

        $recommendations = [];

        // Primary recommendation (best overall time)
        if (! empty($bestTimes)) {
            $best = $bestTimes[0];
            $recommendations[] = [
                'type' => 'primary',
                'title' => 'Mejor Momento General',
                'description' => sprintf(
                    'Publica los %s a las %02d:00 para máximo engagement',
                    $best['day_name'],
                    $best['hour']
                ),
                'day' => $best['day_name'],
                'hour' => $best['hour'],
                'engagement_score' => round($best['avg_engagement'], 2),
                'confidence' => $this->calculateConfidence($best['posts_count']),
            ];
        }

        // Best hour recommendation
        if (! empty($bestHours)) {
            $best = $bestHours[0];
            $recommendations[] = [
                'type' => 'best_hour',
                'title' => 'Mejor Hora del Día',
                'description' => sprintf(
                    'Las %02d:00 es tu mejor hora, independientemente del día',
                    $best['hour']
                ),
                'hour' => $best['hour'],
                'engagement_score' => round($best['avg_engagement'], 2),
                'confidence' => $this->calculateConfidence($best['posts_count']),
            ];
        }

        // Best day recommendation
        if (! empty($bestDays)) {
            $best = $bestDays[0];
            $recommendations[] = [
                'type' => 'best_day',
                'title' => 'Mejor Día de la Semana',
                'description' => sprintf(
                    'Los %s obtienen el mejor engagement promedio',
                    $best['day_name']
                ),
                'day' => $best['day_name'],
                'engagement_score' => round($best['avg_engagement'], 2),
                'confidence' => $this->calculateConfidence($best['posts_count']),
            ];
        }

        // Avoid times (worst performing)
        $worstHours = array_slice(array_reverse($this->calculateBestHours($posts)), 0, 3);
        if (! empty($worstHours)) {
            $avoid = implode(', ', array_map(function ($h) {
                return sprintf('%02d:00', $h['hour']);
            }, $worstHours));

            $recommendations[] = [
                'type' => 'avoid',
                'title' => 'Horas a Evitar',
                'description' => sprintf(
                    'Evita publicar a las %s (bajo engagement)',
                    $avoid
                ),
                'hours' => array_column($worstHours, 'hour'),
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate confidence level based on sample size
     */
    protected function calculateConfidence(int $sampleSize): string
    {
        if ($sampleSize >= 20) {
            return 'high';
        } elseif ($sampleSize >= 10) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get default recommendations when not enough data
     */
    protected function getDefaultRecommendations(string $network): array
    {
        $defaults = [
            'facebook' => [
                'best_hours' => [
                    ['hour' => 13, 'label' => '1:00 PM'],
                    ['hour' => 15, 'label' => '3:00 PM'],
                    ['hour' => 19, 'label' => '7:00 PM'],
                ],
                'best_days' => ['Wednesday', 'Thursday', 'Friday'],
            ],
            'instagram' => [
                'best_hours' => [
                    ['hour' => 11, 'label' => '11:00 AM'],
                    ['hour' => 14, 'label' => '2:00 PM'],
                    ['hour' => 20, 'label' => '8:00 PM'],
                ],
                'best_days' => ['Monday', 'Wednesday', 'Friday'],
            ],
            'twitter' => [
                'best_hours' => [
                    ['hour' => 9, 'label' => '9:00 AM'],
                    ['hour' => 12, 'label' => '12:00 PM'],
                    ['hour' => 17, 'label' => '5:00 PM'],
                ],
                'best_days' => ['Tuesday', 'Wednesday', 'Thursday'],
            ],
            'linkedin' => [
                'best_hours' => [
                    ['hour' => 8, 'label' => '8:00 AM'],
                    ['hour' => 12, 'label' => '12:00 PM'],
                    ['hour' => 17, 'label' => '5:00 PM'],
                ],
                'best_days' => ['Tuesday', 'Wednesday', 'Thursday'],
            ],
        ];

        $default = $defaults[$network] ?? $defaults['facebook'];

        return [
            'best_hours' => $default['best_hours'],
            'best_days' => $default['best_days'],
            'best_times' => [],
            'engagement_by_hour' => [],
            'engagement_by_day' => [],
            'recommendations' => [
                [
                    'type' => 'default',
                    'title' => 'Recomendaciones por Defecto',
                    'description' => 'No hay suficientes datos históricos. Estas son recomendaciones basadas en benchmarks de la industria.',
                    'note' => 'Publica al menos 10 posts para obtener recomendaciones personalizadas.',
                ],
            ],
            'data_points' => 0,
            'insufficient_data' => true,
        ];
    }

    /**
     * Suggest next best posting time
     */
    public function suggestNextPostingTime(SocialAccount $account): ?Carbon
    {
        $analysis = $this->getBestTimesForAccount($account);

        if ($analysis['data_points'] < 10 || empty($analysis['best_times'])) {
            return null;
        }

        $bestTime = $analysis['best_times'][0];

        // Find next occurrence of this day/hour
        $targetDay = $bestTime['day_of_week'];
        $targetHour = $bestTime['hour'];

        $now = now();
        $nextTime = $now->copy();

        // Find next occurrence of target day
        while ($nextTime->dayOfWeek !== $targetDay || $nextTime->hour >= $targetHour) {
            $nextTime->addDay();
        }

        $nextTime->setTime($targetHour, 0, 0);

        return $nextTime;
    }
}
