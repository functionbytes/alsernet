<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Data\SentimentResult;
use Modules\Reviews\Models\Review;

class SentimentAnalysisService
{
    /**
     * Analyze the sentiment of a single review.
     *
     * Primary signal is the star rating. When a comment is present the score
     * is adjusted by counting positive / negative words in the text.
     */
    public function analyze(Review $review): SentimentResult
    {
        $stars = $review->star_rating?->value() ?? 3;

        $baseScore = match (true) {
            $stars >= 4 => 0.6,
            $stars === 3 => 0.0,
            default => -0.6,
        };

        if (! $review->comment) {
            return new SentimentResult(
                sentiment: $this->scoreToSentiment($baseScore),
                score: $baseScore,
                reason: 'star_rating',
            );
        }

        $adjustment = $this->commentAdjustment($review->comment);
        $score = max(-1.0, min(1.0, $baseScore + $adjustment));

        return new SentimentResult(
            sentiment: $this->scoreToSentiment($score),
            score: round($score, 3),
            reason: 'keyword_analysis',
        );
    }

    /**
     * Compute the sentiment distribution (as percentages) for a location over N days.
     *
     * @return array{positive: float, neutral: float, negative: float}
     */
    public function getSentimentDistribution(?int $locationId = null, int $days = 30): array
    {
        $query = Review::query()
            ->where('review_time', '>=', now()->subDays($days))
            ->selectRaw("
                SUM(CASE WHEN star_rating IN ('FOUR','FIVE') THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN star_rating = 'THREE' THEN 1 ELSE 0 END) as neutral,
                SUM(CASE WHEN star_rating IN ('ONE','TWO') THEN 1 ELSE 0 END) as negative,
                COUNT(*) as total
            ");

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        $row = $query->first();
        $total = (int) ($row->total ?? 0);

        if ($total === 0) {
            return ['positive' => 0.0, 'neutral' => 0.0, 'negative' => 0.0];
        }

        return [
            'positive' => round((int) $row->positive / $total * 100, 1),
            'neutral' => round((int) $row->neutral / $total * 100, 1),
            'negative' => round((int) $row->negative / $total * 100, 1),
        ];
    }

    private function commentAdjustment(string $comment): float
    {
        $words = preg_split('/[\s\p{P}]+/u', mb_strtolower($comment));
        $positiveWords = $this->getPositiveWords();
        $negativeWords = $this->getNegativeWords();

        $score = 0.0;

        foreach (($words ?: []) as $word) {
            if (isset($positiveWords[$word])) {
                $score += 0.1;
            } elseif (isset($negativeWords[$word])) {
                $score -= 0.1;
            }
        }

        return max(-0.4, min(0.4, $score));
    }

    private function scoreToSentiment(float $score): string
    {
        if ($score > 0.1) {
            return 'positive';
        }

        if ($score < -0.1) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * @return array<string, true>
     */
    private function getPositiveWords(): array
    {
        $words = [
            'excelente', 'increíble', 'increible', 'perfecto', 'perfecta',
            'maravilloso', 'maravillosa', 'fantástico', 'fantastico',
            'espectacular', 'genial', 'bueno', 'buena', 'limpio', 'limpia',
            'rápido', 'rapido', 'amable', 'amables', 'atento', 'atenta',
            'recomiendo', 'recomendable', 'satisfecho', 'satisfecha',
            'delicioso', 'deliciosa', 'profesional', 'eficiente', 'agradable',
            'excellent', 'amazing', 'perfect', 'wonderful', 'fantastic',
            'great', 'good', 'clean', 'fast', 'friendly', 'helpful',
            'recommend', 'satisfied', 'delicious', 'professional', 'efficient',
            'pleasant', 'awesome', 'outstanding', 'superb', 'love', 'best',
        ];

        return array_fill_keys($words, true);
    }

    /**
     * @return array<string, true>
     */
    private function getNegativeWords(): array
    {
        $words = [
            'malo', 'mala', 'pésimo', 'pesimo', 'terrible', 'horrible',
            'desastroso', 'sucio', 'sucia', 'lento', 'lenta', 'descortés',
            'grosero', 'grosera', 'decepcionante', 'decepcionado', 'caro', 'cara',
            'problema', 'queja', 'error', 'espera', 'demora', 'tardaron',
            'bad', 'awful', 'disgusting', 'dirty', 'slow', 'rude',
            'disappointing', 'expensive', 'ugly', 'problem', 'complaint',
            'wrong', 'worst', 'poor', 'unfriendly', 'cold',
        ];

        return array_fill_keys($words, true);
    }
}
