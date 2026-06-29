<?php

namespace Modules\HelpdeskAnalytics\Services;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;

/**
 * Batch version of CustomerInsightsService::healthScore.
 *
 * The original computes ~4 queries per customer; called in a loop over the
 * at-risk report it produced ~200 queries/request. This resolves the same four
 * aggregates with one grouped query each, then scores in PHP — O(1) queries
 * regardless of the number of customers.
 */
class HealthScoreBatchService
{
    /**
     * @param  array<int, int>  $customerIds
     * @return array<int, int> [customerId => score 0..100]
     */
    public function scoresFor(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_filter($customerIds)));

        if ($customerIds === []) {
            return [];
        }

        $csatAvg = CsatRating::query()
            ->whereIn('customer_id', $customerIds)
            ->whereNotNull('answered_at')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, AVG(rating) as avg_rating')
            ->pluck('avg_rating', 'customer_id');

        $closedCounts = Conversation::query()
            ->whereIn('customer_id', $customerIds)
            ->whereNotNull('closed_at')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, COUNT(*) as cnt')
            ->pluck('cnt', 'customer_id');

        $lastContact = Conversation::query()
            ->whereIn('customer_id', $customerIds)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(created_at) as last_at')
            ->pluck('last_at', 'customer_id');

        $negativeSentiment = DB::connection('helpdesk')
            ->table('helpdesk_conversation_tag_pivot as pivot')
            ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
            ->whereIn('c.customer_id', $customerIds)
            ->whereIn('t.slug', ['sentiment-negative', 'sentiment_negative'])
            ->where('pivot.created_at', '>=', now()->subDays(30))
            ->distinct()
            ->pluck('c.customer_id')
            ->flip();

        $scores = [];

        foreach ($customerIds as $id) {
            $score = 50;

            if (($csatAvg[$id] ?? null) !== null && (float) $csatAvg[$id] >= 4) {
                $score += 30;
            }

            $score += intdiv((int) ($closedCounts[$id] ?? 0), 5) * 20;

            $last = $lastContact[$id] ?? null;
            if ($last && abs(now()->diffInMonths($last)) > 6) {
                $score -= 20;
            }

            if ($negativeSentiment->has($id)) {
                $score -= 30;
            }

            $scores[$id] = max(0, min(100, $score));
        }

        return $scores;
    }
}
