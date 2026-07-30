<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;

class CustomerInsightsService
{
    /**
     * Calculate a 0-100 health score for a customer.
     *
     * Scoring rules:
     *   +30  CSAT average >= 4
     *   +20  per every 5 successfully closed conversations
     *   -20  last conversation was more than 6 months ago
     *   -30  tag slug 'sentiment-negative' applied in the last 30 days
     *
     * Cached for 15 minutes per customer — this individual lookup is used
     * when opening a single customer profile; the batch healthScoresFor()
     * used by reports is not cached here (already O(1) queries).
     */
    public function healthScore(Customer $customer): int
    {
        return Cache::remember(
            "helpdesk:health-score:{$customer->id}",
            900,
            fn (): int => $this->calculateHealthScore($customer)
        );
    }

    private function calculateHealthScore(Customer $customer): int
    {
        $score = 50; // base neutral

        $csatAvg = CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->avg('rating');

        if ($csatAvg !== null && $csatAvg >= 4) {
            $score += 30;
        }

        $closedCount = Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('closed_at')
            ->count();

        $score += intdiv($closedCount, 5) * 20;

        $lastConv = Conversation::query()
            ->where('customer_id', $customer->id)
            ->latest('created_at')
            ->value('created_at');

        if ($lastConv && now()->diffInMonths($lastConv, true) > 6) {
            $score -= 20;
        }

        $hasNegativeSentiment = DB::connection('helpdesk')
            ->table('helpdesk_conversation_tag_pivot as pivot')
            ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
            ->where('c.customer_id', $customer->id)
            ->whereIn('t.slug', ['sentiment-negative', 'sentiment_negative'])
            ->where('pivot.created_at', '>=', now()->subDays(30))
            ->exists();

        if ($hasNegativeSentiment) {
            $score -= 30;
        }

        return max(0, min(100, $score));
    }

    /**
     * Versión batch de {@see healthScore()}: resuelve los mismos cuatro
     * agregados con una consulta agrupada cada uno en vez de ~4 consultas por
     * cliente, así un report que puntúa N clientes se mantiene O(1) en consultas.
     * Produce puntuaciones idénticas al método individual.
     *
     * @param  array<int, int>  $customerIds
     * @return array<int, int> [customerId => score 0..100]
     */
    public function healthScoresFor(array $customerIds): array
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

    /**
     * Return lifetime engagement metrics for a customer.
     *
     * @return array{conversations: int, messages: int, first_contact: ?string, last_contact: ?string, csat_avg: float, avg_response_time_seconds: int, channels_used: array<string>}
     */
    public function lifetimeMetrics(Customer $customer): array
    {
        $convStats = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $customer->id)
            ->selectRaw('
                COUNT(*) as conversations,
                MIN(created_at) as first_contact,
                MAX(created_at) as last_contact
            ')
            ->first();

        $messageCount = DB::connection('helpdesk')
            ->table('helpdesk_conversation_items as ci')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'ci.conversation_id')
            ->where('c.customer_id', $customer->id)
            ->where('ci.type', 'message')
            ->count();

        $csatAvg = CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->avg('rating') ?? 0.0;

        $avgResponseSeconds = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $customer->id)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_sec')
            ->value('avg_sec') ?? 0;

        $channels = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $customer->id)
            ->whereNotNull('channel')
            ->distinct()
            ->pluck('channel')
            ->values()
            ->all();

        return [
            'conversations' => (int) ($convStats->conversations ?? 0),
            'messages' => $messageCount,
            'first_contact' => $convStats->first_contact ?? null,
            'last_contact' => $convStats->last_contact ?? null,
            'csat_avg' => round((float) $csatAvg, 2),
            'avg_response_time_seconds' => (int) round($avgResponseSeconds),
            'channels_used' => $channels,
        ];
    }

    /**
     * Return a chronological timeline of key customer events.
     *
     * @return array<int, array{type: string, label: string, description: string, occurred_at: string}>
     */
    public function journeyTimeline(Customer $customer, int $limit = 30): array
    {
        $events = [];

        // Conversation start / end events
        $conversations = Conversation::query()
            ->where('customer_id', $customer->id)
            ->select(['id', 'subject', 'channel', 'created_at', 'closed_at'])
            ->latest('created_at')
            ->limit($limit)
            ->get();

        foreach ($conversations as $conv) {
            $label = $conv->subject ?: "Conversacion #{$conv->id}";

            $events[] = [
                'type' => 'conversation_started',
                'label' => 'Conversacion iniciada',
                'description' => $label.' ('.$conv->channel.')',
                'occurred_at' => $conv->created_at->toIso8601String(),
            ];

            if ($conv->closed_at) {
                $events[] = [
                    'type' => 'conversation_closed',
                    'label' => 'Conversacion cerrada',
                    'description' => $label,
                    'occurred_at' => $conv->closed_at->toIso8601String(),
                ];
            }
        }

        // CSAT responses
        $csats = CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->select(['id', 'conversation_id', 'rating', 'answered_at'])
            ->latest('answered_at')
            ->limit($limit)
            ->get();

        foreach ($csats as $csat) {
            $events[] = [
                'type' => 'csat_submitted',
                'label' => 'CSAT respondido',
                'description' => "Valoracion: {$csat->rating}/5",
                'occurred_at' => $csat->answered_at->toIso8601String(),
            ];
        }

        // Tags applied
        $tags = DB::connection('helpdesk')
            ->table('helpdesk_conversation_tag_pivot as pivot')
            ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
            ->where('c.customer_id', $customer->id)
            ->select(['t.name as tag_name', 'pivot.created_at'])
            ->orderByDesc('pivot.created_at')
            ->limit($limit)
            ->get();

        foreach ($tags as $tag) {
            $events[] = [
                'type' => 'tag_applied',
                'label' => 'Etiqueta aplicada',
                'description' => $tag->tag_name,
                'occurred_at' => $tag->created_at,
            ];
        }

        // Sort by occurred_at desc, take $limit
        usort($events, fn ($a, $b) => strcmp($b['occurred_at'], $a['occurred_at']));

        return array_slice($events, 0, $limit);
    }
}
