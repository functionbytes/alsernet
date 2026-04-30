<?php

namespace Modules\Chat\Services;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Models\Conversations\Conversation;

/**
 * Centralized service for conversation statistics and counts with Redis caching.
 *
 * Provides unified cache keys and invalidation for all conversation count queries
 * used across dashboard, sidebar filters, and analytics.
 */
class ConversationStatsService
{
    public function __construct(
        private readonly int $accountId
    ) {}

    /**
     * Get all filter counts for the sidebar navigation.
     *
     * Returns counts for: all, open, resolved, pending, mine, unassigned, mentions, unattended
     * plus priority and status breakdowns.
     *
     * Cached for 30 seconds with user-specific key (for mentions count).
     *
     * @return array{
     *     all: int,
     *     open: int,
     *     resolved: int,
     *     pending: int,
     *     mine: int,
     *     unassigned: int,
     *     mentions: int,
     *     unattended: int,
     *     urgent: int,
     *     high: int,
     *     medium: int,
     *     low: int,
     *     closed: int
     * }
     */
    public function getFilterCounts(int $userId, string $userName): array
    {
        $cacheKey = $this->buildCacheKey('filter_counts', $userId);

        return Cache::remember($cacheKey, 30, function () use ($userId, $userName): array {
            $row = Conversation::forAccount($this->accountId)
                ->aggregatedFilterCounts($userId, $userName)
                ->first();

            return [
                'all' => (int) $row->total,
                'open' => (int) $row->open_count,
                'resolved' => (int) $row->resolved_count,
                'pending' => (int) $row->pending_count,
                'closed' => (int) $row->closed_count,
                'mine' => (int) $row->mine_count,
                'unassigned' => (int) $row->unassigned_count,
                'mentions' => (int) $row->mentions_count,
                'unattended' => (int) $row->unattended_count,
                'urgent' => (int) $row->urgent_count,
                'high' => (int) $row->high_count,
                'medium' => (int) $row->medium_count,
                'low' => (int) $row->low_count,
            ];
        });
    }

    /**
     * Get count of conversations where user is mentioned.
     */
    public function getMentionsCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['mentions'];
    }

    /**
     * Get count of unattended conversations (no agent has replied yet).
     */
    public function getUnattendedCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['unattended'];
    }

    /**
     * Get count of open conversations.
     */
    public function getOpenCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['open'];
    }

    /**
     * Get count of resolved conversations.
     */
    public function getResolvedCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['resolved'];
    }

    /**
     * Get count of pending conversations.
     */
    public function getPendingCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['pending'];
    }

    /**
     * Get count of conversations assigned to user.
     */
    public function getMineCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['mine'];
    }

    /**
     * Get count of unassigned conversations.
     */
    public function getUnassignedCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['unassigned'];
    }

    /**
     * Get total conversation count.
     */
    public function getTotalCount(int $userId, string $userName): int
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return $counts['all'];
    }

    /**
     * Get priority counts breakdown.
     *
     * @return array{urgent: int, high: int, medium: int, low: int}
     */
    public function getPriorityCounts(int $userId, string $userName): array
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return [
            'urgent' => $counts['urgent'],
            'high' => $counts['high'],
            'medium' => $counts['medium'],
            'low' => $counts['low'],
        ];
    }

    /**
     * Get status counts breakdown.
     *
     * @return array{open: int, pending: int, resolved: int, closed: int}
     */
    public function getStatusCounts(int $userId, string $userName): array
    {
        $counts = $this->getFilterCounts($userId, $userName);

        return [
            'open' => $counts['open'],
            'pending' => $counts['pending'],
            'resolved' => $counts['resolved'],
            'closed' => $counts['closed'],
        ];
    }

    /**
     * Invalidate conversation stats cache for a specific user.
     *
     * Call this from ConversationObserver when conversations change.
     */
    public function invalidateCache(?int $userId = null): void
    {
        if ($userId !== null) {
            $key = $this->buildCacheKey('filter_counts', $userId);
            Cache::forget($key);

            return;
        }

        $this->invalidateAllCaches();
    }

    /**
     * Invalidate all conversation stats caches for this account.
     *
     * This is a brute-force invalidation — use when you don't know
     * which specific user caches to invalidate (e.g., bulk operations).
     *
     * Note: For non-Redis cache drivers, we can't pattern-match keys,
     * so we accept this as a known limitation. Individual user cache
     * invalidation via invalidateCache($userId) is preferred.
     */
    public function invalidateAllCaches(): void
    {
        try {
            if (Cache::getStore() instanceof RedisStore) {
                $pattern = "conversations:stats:{$this->accountId}:*";
                $keys = Cache::getRedis()->keys($pattern);

                if (! empty($keys)) {
                    foreach ($keys as $key) {
                        $keyName = str_replace(Cache::getPrefix(), '', $key);
                        Cache::forget($keyName);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silent fail for non-Redis cache drivers — pattern invalidation not supported
        }
    }

    /**
     * Build a cache key for conversation stats.
     *
     * @param  string  $type  The type of stat (e.g., 'filter_counts')
     * @param  int|null  $userId  Optional user ID for user-specific stats
     */
    private function buildCacheKey(string $type, ?int $userId = null): string
    {
        if ($userId !== null) {
            return "conversations:stats:{$this->accountId}:{$userId}:{$type}";
        }

        return "conversations:stats:{$this->accountId}:{$type}";
    }
}
