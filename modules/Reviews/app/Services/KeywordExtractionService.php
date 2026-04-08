<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Models\Review;

class KeywordExtractionService
{
    /**
     * Extract the top N keywords from review comments for a location over the last N days.
     *
     * @return array<int, array{word: string, count: int, sentiment: string}>
     */
    public function extractKeywords(int $locationId, int $days = 30, int $limit = 20): array
    {
        $comments = Review::query()
            ->where('location_id', $locationId)
            ->whereNotNull('comment')
            ->where('review_time', '>=', now()->subDays($days))
            ->pluck('comment');

        if ($comments->isEmpty()) {
            return [];
        }

        $stopwords = $this->getStopwords();
        $frequency = [];

        foreach ($comments as $comment) {
            $words = $this->tokenize($comment);

            foreach ($words as $word) {
                if (mb_strlen($word) < 3 || isset($stopwords[$word])) {
                    continue;
                }

                $frequency[$word] = ($frequency[$word] ?? 0) + 1;
            }
        }

        arsort($frequency);
        $top = array_slice($frequency, 0, $limit, true);

        $positiveWords = $this->getPositiveWords();
        $negativeWords = $this->getNegativeWords();

        return array_values(
            array_map(fn (string $word, int $count) => [
                'word' => $word,
                'count' => $count,
                'sentiment' => $this->classifyWordSentiment($word, $positiveWords, $negativeWords),
            ], array_keys($top), $top)
        );
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $lower = mb_strtolower($text);
        $clean = preg_replace('/[^\p{L}\s]/u', ' ', $lower);

        return array_filter(preg_split('/\s+/', $clean ?? ''));
    }

    private function classifyWordSentiment(string $word, array $positiveWords, array $negativeWords): string
    {
        if (isset($positiveWords[$word])) {
            return 'positive';
        }

        if (isset($negativeWords[$word])) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Spanish and English stopwords as a hash map for O(1) lookup.
     *
     * @return array<string, true>
     */
    private function getStopwords(): array
    {
        $words = [
            // Spanish
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
            'de', 'del', 'al', 'en', 'que', 'y', 'a', 'con', 'por', 'para',
            'es', 'son', 'fue', 'era', 'ser', 'estar', 'hay', 'han', 'haber',
            'si', 'no', 'se', 'le', 'lo', 'me', 'mi', 'su', 'tu',
            'pero', 'porque', 'como', 'más', 'muy', 'bien', 'mal',
            'todo', 'todos', 'toda', 'todas', 'este', 'esta', 'estos', 'estas',
            'ese', 'esa', 'eso', 'aqui', 'aquí', 'allí', 'cuando', 'donde',
            'nos', 'les', 'ellos', 'ellas', 'yo', 'tú', 'él', 'ella',
            'usted', 'ustedes', 'nuestro', 'nuestra', 'vuestro', 'vuestra',
            'también', 'aunque', 'así', 'ante', 'bajo', 'cabe', 'desde',
            'durante', 'entre', 'hacia', 'hasta', 'mediante', 'salvo',
            'según', 'sin', 'sobre', 'tras', 'que', 'qui', 'tan',
            // English
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
            'for', 'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were',
            'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'may', 'might', 'shall',
            'not', 'no', 'nor', 'so', 'yet', 'both', 'either', 'neither',
            'this', 'that', 'these', 'those', 'my', 'your', 'his', 'her',
            'its', 'our', 'their', 'it', 'we', 'they', 'he', 'she', 'you',
            'i', 'me', 'him', 'us', 'them', 'who', 'which', 'what', 'when',
            'where', 'how', 'why', 'all', 'each', 'every', 'more', 'most',
            'also', 'just', 'very', 'too', 'as', 'up', 'out', 'about',
        ];

        return array_fill_keys($words, true);
    }

    /**
     * @return array<string, true>
     */
    private function getPositiveWords(): array
    {
        $words = [
            // Spanish
            'excelente', 'increíble', 'increible', 'perfecto', 'perfecta',
            'maravilloso', 'maravillosa', 'fantástico', 'fantastico',
            'espectacular', 'genial', 'bueno', 'buena', 'buenos', 'buenas',
            'limpio', 'limpia', 'limpios', 'limpias', 'rápido', 'rapido',
            'rápida', 'amable', 'amables', 'atento', 'atenta', 'atentos',
            'recomiendo', 'recomendable', 'satisfecho', 'satisfecha',
            'delicioso', 'deliciosa', 'rico', 'rica', 'sabroso', 'sabrosa',
            'profesional', 'profesionales', 'eficiente', 'eficientes',
            'agradable', 'agradables', 'bonito', 'bonita', 'hermoso', 'hermosa',
            // English
            'excellent', 'amazing', 'perfect', 'wonderful', 'fantastic',
            'great', 'good', 'clean', 'fast', 'quick', 'friendly', 'helpful',
            'recommend', 'satisfied', 'delicious', 'tasty', 'professional',
            'efficient', 'pleasant', 'beautiful', 'awesome', 'outstanding',
            'superb', 'love', 'loved', 'best', 'happy', 'pleased',
        ];

        return array_fill_keys($words, true);
    }

    /**
     * @return array<string, true>
     */
    private function getNegativeWords(): array
    {
        $words = [
            // Spanish
            'malo', 'mala', 'malos', 'malas', 'pésimo', 'pesimo',
            'terrible', 'horrible', 'desastroso', 'desastrosa',
            'sucio', 'sucia', 'sucios', 'sucias', 'lento', 'lenta',
            'descortés', 'descortes', 'grosero', 'grosera',
            'decepcionante', 'decepcionado', 'decepcionada',
            'caro', 'cara', 'caros', 'caras', 'feo', 'fea',
            'espera', 'demora', 'tardaron', 'tardanza',
            'problema', 'problemas', 'queja', 'quejas', 'error', 'errores',
            // English
            'bad', 'terrible', 'horrible', 'awful', 'disgusting',
            'dirty', 'slow', 'rude', 'disappointing', 'disappointed',
            'expensive', 'overpriced', 'ugly', 'wait', 'waiting', 'delay',
            'problem', 'issue', 'complaint', 'error', 'wrong', 'worst',
            'poor', 'mediocre', 'unfriendly', 'unhelpful', 'cold',
        ];

        return array_fill_keys($words, true);
    }
}
