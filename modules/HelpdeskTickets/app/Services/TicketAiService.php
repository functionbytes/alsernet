<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketItem;

class TicketAiService
{
    private const NEGATIVE_KEYWORDS = [
        'horrible', 'pesimo', 'terrible', 'molest', 'frustr', 'enojad',
        'decepcion', 'no funciona', 'error', 'problema', 'urgente', 'bug',
        'cobrad', 'hate', 'angry', 'frustrated', 'awful', 'worst',
    ];

    private const POSITIVE_KEYWORDS = [
        'gracias', 'genial', 'excelente', 'perfect', 'resuelto', 'funciona',
        'funciono', 'thanks', 'thank you', 'great', 'awesome', 'perfect',
        'excellent', 'love',
    ];

    private const URGENT_KEYWORDS = [
        'urgente', 'urgent', 'asap', 'critical', 'critico', 'parado',
        'caido', 'down', 'outage', 'no funciona', 'emergency',
    ];

    private const HIGH_KEYWORDS = [
        'importante', 'priority', 'problema', 'bug', 'error', 'rapido',
    ];

    /**
     * Analyze sentiment of a text string using keyword-based rules.
     *
     * @return array{sentiment: string, score: float}
     */
    public function analyzeSentiment(string $text): array
    {
        $text = mb_strtolower($text);

        $negCount = $this->countKeywords($text, self::NEGATIVE_KEYWORDS);
        $posCount = $this->countKeywords($text, self::POSITIVE_KEYWORDS);

        if ($negCount > $posCount && $negCount >= 1) {
            return ['sentiment' => 'negative', 'score' => -min(1.0, 0.3 + $negCount * 0.15)];
        }

        if ($posCount > $negCount) {
            return ['sentiment' => 'positive', 'score' => min(1.0, 0.3 + $posCount * 0.15)];
        }

        return ['sentiment' => 'neutral', 'score' => 0.0];
    }

    /**
     * Suggest the most appropriate category for a ticket based on keyword matching.
     */
    public function suggestCategory(Ticket $ticket): ?int
    {
        $text = strtolower(($ticket->subject ?? '').' '.($ticket->description ?? ''));
        $categories = TicketCategory::active()->get();

        $scores = [];

        foreach ($categories as $cat) {
            $score = $this->scoreByKeywords($text, strtolower($cat->name), 3, 1.0);
            $score += $this->scoreByKeywords($text, strtolower((string) $cat->description), 4, 0.5);

            if ($score > 0) {
                $scores[$cat->id] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);

        return array_key_first($scores);
    }

    /**
     * Suggest an appropriate priority based on ticket content.
     */
    public function suggestPriority(Ticket $ticket): string
    {
        $text = strtolower(($ticket->subject ?? '').' '.($ticket->description ?? ''));

        foreach (self::URGENT_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                return 'urgent';
            }
        }

        foreach (self::HIGH_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                return 'high';
            }
        }

        return 'normal';
    }

    /**
     * Generate smart reply suggestions based on the last customer message.
     *
     * @return array<int, string>
     */
    public function smartReplySuggestions(Ticket $ticket): array
    {
        $lastMessage = $ticket->items()
            ->where('is_internal', false)
            ->latest()
            ->first();

        if (! $lastMessage) {
            return [];
        }

        $text = strtolower($lastMessage->body ?? '');
        $suggestions = [];

        if (str_contains($text, 'gracias') || str_contains($text, 'thank')) {
            $suggestions[] = 'De nada, si necesitas cualquier otra cosa estamos aqui.';
            $suggestions[] = 'Gracias a ti por tu paciencia.';
        }

        if (str_contains($text, '?') || str_contains($text, 'como ') || str_contains($text, 'how ')) {
            $suggestions[] = 'Permiteme revisar esto y te respondo en unos minutos.';
            $suggestions[] = 'Te envio mas detalles por email.';
        }

        if (str_contains($text, 'no funciona') || str_contains($text, 'error') || str_contains($text, 'bug')) {
            $suggestions[] = 'Lamento las molestias. Estamos revisando el problema.';
            $suggestions[] = 'Podrias enviarnos una captura del error y los pasos para reproducirlo?';
        }

        if (empty($suggestions)) {
            $suggestions = [
                'Gracias por contactarnos. Estamos revisando tu caso.',
                'Permitenos investigar y te respondemos a la brevedad.',
            ];
        }

        return array_slice($suggestions, 0, 3);
    }

    /**
     * Analyze sentiment for a TicketItem and update the ticket's average.
     */
    public function analyzeAndTag(TicketItem $item): void
    {
        // Only analyze customer messages (not internal notes or agent replies)
        if ($item->is_internal || (! $item->author_id && $item->user_id)) {
            return;
        }

        $result = $this->analyzeSentiment($item->body ?? '');

        $item->update([
            'sentiment' => $result['sentiment'],
            'sentiment_score' => $result['score'],
        ]);

        $ticket = $item->ticket;

        if (! $ticket) {
            return;
        }

        $avg = $ticket->items()
            ->whereNotNull('sentiment_score')
            ->where('is_internal', false)
            ->avg('sentiment_score');

        $ticket->update(['customer_sentiment_avg' => $avg]);
    }

    /**
     * Auto-classify ticket: suggest category and priority when not already set.
     */
    public function autoClassify(Ticket $ticket): void
    {
        if (! $ticket->category_id) {
            $suggested = $this->suggestCategory($ticket);
            if ($suggested) {
                $ticket->update(['ai_suggested_category_id' => $suggested]);
            }
        }

        $currentPriority = $ticket->priority ?? 'normal';

        if ($currentPriority === 'normal') {
            $suggested = $this->suggestPriority($ticket);
            if ($suggested !== 'normal') {
                $ticket->update(['ai_suggested_priority' => $suggested]);
            }
        }
    }

    private function countKeywords(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $kw) {
            $count += substr_count($text, $kw);
        }

        return $count;
    }

    private function scoreByKeywords(string $text, string $source, int $minLength, float $weight): float
    {
        $score = 0.0;
        foreach (array_filter(explode(' ', $source)) as $kw) {
            if (strlen($kw) >= $minLength && str_contains($text, $kw)) {
                $score += $weight;
            }
        }

        return $score;
    }
}
