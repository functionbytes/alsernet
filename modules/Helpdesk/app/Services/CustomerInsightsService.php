<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;

class CustomerInsightsService
{
    /**
     * Memoria de la petición en curso — mismo patrón que Setting::$memo
     * (Modules\Helpdesk\Models\Setting). healthScore(), healthFactors() y
     * lifetimeMetrics() comparten los mismos cuatro agregados (avg CSAT,
     * conversaciones cerradas, última conversación, sentimiento negativo
     * reciente); buildResumen() (ContactAggregatorService, panel "Cliente" de
     * Tickets) los pedía tres veces en la misma petición — medido el
     * 28-sep-2026: avg(rating) x3, COUNT(closed_at) x2, última conversación
     * x2, exists(tag_pivot) x2. aggregates() los calcula una sola vez por
     * cliente y petición. En workers de cola el proceso no muere entre jobs:
     * HelpdeskServiceProvider::registerSettingMemoReset() la vacía al
     * terminar cada request y cada job, igual que Setting::$memo.
     *
     * @var array<int, array{csat_avg: ?float, closed_conversations_count: int, last_conversation_at: ?string, has_negative_sentiment_30d: bool}>
     */
    private static array $aggregatesMemo = [];

    /**
     * Olvida lo memoizado — igual que Setting::forgetMemo(), la necesitan los
     * tests (TestCase::setUp()) para no arrastrar agregados de un test al
     * siguiente dentro del mismo proceso de PHPUnit.
     */
    public static function forgetMemo(): void
    {
        self::$aggregatesMemo = [];
    }

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
        $score = 50 + array_sum(array_column($this->healthFactors($customer), 'points'));

        return max(0, min(100, $score));
    }

    /**
     * Desglose de la puntuación de salud: cada regla con los puntos que
     * aporta a este cliente (0 si no aplica). Parte de una base de 50;
     * calculateHealthScore() suma exactamente estos puntos.
     *
     * @return array<int, array{key: string, label: string, points: int, max: int}>
     */
    public function healthFactors(Customer $customer): array
    {
        $aggregates = $this->aggregates($customer);

        $csatAvg = $aggregates['csat_avg'];
        $closedCount = $aggregates['closed_conversations_count'];
        $lastConv = $aggregates['last_conversation_at'] ? Carbon::parse($aggregates['last_conversation_at']) : null;
        $hasNegativeSentiment = $aggregates['has_negative_sentiment_30d'];

        return [
            ['key' => 'csat', 'label' => 'Satisfacción (CSAT ≥ 4)', 'points' => $csatAvg !== null && $csatAvg >= 4 ? 30 : 0, 'max' => 30],
            ['key' => 'resolved', 'label' => 'Conversaciones resueltas', 'points' => intdiv($closedCount, 5) * 20, 'max' => 50],
            ['key' => 'recency', 'label' => 'Contacto en los últimos 6 meses', 'points' => $lastConv && now()->diffInMonths($lastConv, true) > 6 ? -20 : 0, 'max' => 20],
            ['key' => 'sentiment', 'label' => 'Sentimiento negativo (30 días)', 'points' => $hasNegativeSentiment ? -30 : 0, 'max' => 30],
        ];
    }

    /**
     * Los cuatro agregados de salud del cliente (avg CSAT, conversaciones
     * cerradas, última conversación, sentimiento negativo en 30 días),
     * calculados una sola vez y compartidos por healthFactors()/
     * calculateHealthScore()/lifetimeMetrics() — ver el porqué en
     * {@see self::$aggregatesMemo}.
     *
     * @return array{csat_avg: ?float, closed_conversations_count: int, last_conversation_at: ?string, has_negative_sentiment_30d: bool}
     */
    private function aggregates(Customer $customer): array
    {
        // Sin Cache::remember a propósito: lifetimeMetrics() alimenta
        // CustomerInsightsController en vivo y healthScore() ya cachea su
        // resultado 900 s por su cuenta — aquí solo se deduplica dentro de
        // la petición/job en curso.
        return self::$aggregatesMemo[$customer->id] ??= (function () use ($customer): array {
            $csatAvg = CsatRating::query()
                ->where('customer_id', $customer->id)
                ->whereNotNull('answered_at')
                ->avg('rating');

            $closedCount = Conversation::query()
                ->where('customer_id', $customer->id)
                ->whereNotNull('closed_at')
                ->count();

            $lastConversationAt = Conversation::query()
                ->where('customer_id', $customer->id)
                ->latest('created_at')
                ->value('created_at');

            $hasNegativeSentiment = DB::connection('helpdesk')
                ->table('helpdesk_conversation_tag_pivot as pivot')
                ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
                ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
                ->where('c.customer_id', $customer->id)
                ->whereIn('t.slug', ['sentiment-negative', 'sentiment_negative'])
                ->where('pivot.created_at', '>=', now()->subDays(30))
                ->exists();

            return [
                'csat_avg' => $csatAvg !== null ? (float) $csatAvg : null,
                'closed_conversations_count' => (int) $closedCount,
                'last_conversation_at' => $lastConversationAt?->toIso8601String(),
                'has_negative_sentiment_30d' => $hasNegativeSentiment,
            ];
        })();
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
     * @return array{conversations: int, messages: int, first_contact: ?string, last_contact: ?string, csat_avg: ?float, avg_response_time_seconds: int, channels_used: array<string>}
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

        // null real (nunca respondió ninguna encuesta) distinto de 0.0 (un
        // promedio real de valoraciones bajas) — bug de honestidad de datos
        // encontrado en QA visual: al colapsar "sin datos" en 0.0 aquí, el
        // panel "Cliente" de Tickets (vía ContactAggregatorService) mostraba
        // el chip "CSAT 0" para clientes que nunca fueron encuestados, como
        // si tuvieran la peor valoración posible.
        // Sale de aggregates() (compartido con healthFactors()) en vez de su
        // propio avg('rating'): era la 3ª vez que se calculaba lo mismo.
        $csatAvg = $this->aggregates($customer)['csat_avg'];

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
            'csat_avg' => $csatAvg !== null ? round((float) $csatAvg, 2) : null,
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
