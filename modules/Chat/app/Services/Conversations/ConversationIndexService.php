<?php

namespace Modules\Chat\Services\Conversations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Services\ConversationStatsService;

/**
 * Builds the shared sidebar data (counts, inboxes, teams, labels) used by
 * every conversation index view (main list, mine, unassigned, byInbox, etc.).
 */
class ConversationIndexService
{
    public function __construct(
        private readonly ConversationStatsService $statsService
    ) {}

    /**
     * Return all data arrays needed by the conversation index views.
     *
     * @return array{
     *     filterCounts: array<string, int>,
     *     priorityCounts: array<string, int>,
     *     statusCounts: array<string, int>,
     *     inboxes: \Illuminate\Database\Eloquent\Collection,
     *     teams: \Illuminate\Database\Eloquent\Collection,
     *     labels: \Illuminate\Database\Eloquent\Collection,
     *     labelsByTitle: \Illuminate\Database\Eloquent\Collection, keyed by label `name`
     * }
     */
    public function buildIndexData(int $accountId, int $userId, string $userName): array
    {
        $counts = $this->statsService->getFilterCounts($userId, $userName);
        $labels = $this->cachedLabels($accountId);

        return [
            'filterCounts' => [
                'all' => $counts['all'],
                'open' => $counts['open'],
                'resolved' => $counts['resolved'],
                'pending' => $counts['pending'],
                'mine' => $counts['mine'],
                'unassigned' => $counts['unassigned'],
                'mentions' => $counts['mentions'],
                'unattended' => $counts['unattended'],
            ],
            'priorityCounts' => [
                'urgent' => $counts['urgent'],
                'high' => $counts['high'],
                'medium' => $counts['medium'],
                'low' => $counts['low'],
            ],
            'statusCounts' => [
                'open' => $counts['open'],
                'pending' => $counts['pending'],
                'resolved' => $counts['resolved'],
                'closed' => $counts['closed'],
            ],
            'inboxes' => $this->cachedInboxes($accountId),
            'teams' => $this->cachedTeams($accountId),
            'labels' => $labels,
            'labelsByTitle' => $labels->keyBy('name'),
        ];
    }

    /**
     * Inboxes with conversation counts and channel relationship (TTL: 5 min — rarely change).
     *
     * withCount() avoids N+1; channel is needed for sidebar icons (5 extra queries for
     * polymorphic types — unavoidable without denormalizing channel_icon to inboxes).
     */
    private function cachedInboxes(int $accountId): Collection
    {
        return Cache::remember("chat:inboxes:{$accountId}", 300, fn () => Inbox::forAccount($accountId)
            ->withCount(['conversations' => fn ($q) => $q->forAccount($accountId)])
            ->with('channel')
            ->get());
    }

    /**
     * Teams with conversation counts (TTL: 5 min — rarely change).
     */
    private function cachedTeams(int $accountId): Collection
    {
        return Cache::remember("chat:teams:{$accountId}", 300, fn () => Team::forAccount($accountId)
            ->withCount(['conversations' => fn ($q) => $q->forAccount($accountId)])
            ->get());
    }

    /**
     * Labels with per-label conversation counts (TTL: 60 s — moderately volatile).
     *
     * @return Collection<int, ConversationLabel>
     */
    private function cachedLabels(int $accountId): Collection
    {
        return Cache::remember("chat:labels:{$accountId}", 60, fn () => $this->labelsWithCounts($accountId));
    }

    /**
     * Fetch all labels for the account and attach a conversations_count to each.
     *
     * Uses a single query to fetch all cached_label_list values then counts in PHP.
     * The label field is `name` (not `title`) — cached_label_list stores names directly.
     *
     * @return Collection<int, ConversationLabel>
     */
    private function labelsWithCounts(int $accountId): Collection
    {
        $labels = ConversationLabel::forAccount($accountId)->get();

        if ($labels->isEmpty()) {
            return $labels;
        }

        $countsByName = Conversation::labelCountsForAccount($accountId, $labels->pluck('name')->all());

        return $labels->each(function (ConversationLabel $label) use ($countsByName): void {
            $label->conversations_count = $countsByName[$label->name] ?? 0;
        });
    }
}
