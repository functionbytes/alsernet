<?php

namespace Modules\Reviews\Services;

class LanguageDetectionService
{
    private const DEFAULT_LANGUAGE = 'es';

    /**
     * Detect the language of a text using heuristic word matching.
     * Returns ISO 639-1 code, defaults to 'es'.
     */
    public function detect(string $text): string
    {
        if (empty(trim($text))) {
            return self::DEFAULT_LANGUAGE;
        }

        $normalised = mb_strtolower($text);
        $wordLists = $this->getLanguageWordLists();
        $scores = [];

        foreach ($wordLists as $lang => $words) {
            $scores[$lang] = 0;
            foreach ($words as $word) {
                // Match whole-word occurrences
                if (preg_match('/\b'.preg_quote($word, '/').'\b/u', $normalised)) {
                    $scores[$lang]++;
                }
            }
        }

        $best = array_search(max($scores), $scores, true);

        // Only accept a winner if it scored at least one match
        if ($scores[$best] === 0) {
            return self::DEFAULT_LANGUAGE;
        }

        return (string) $best;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getLanguageWordLists(): array
    {
        return [
            'es' => [
                'gracias', 'bueno', 'excelente', 'muy', 'bien', 'lugar',
                'servicio', 'recomiendo', 'atención', 'precio', 'calidad',
                'malo', 'horrible', 'pésimo', 'lamentable', 'increíble',
                'personal', 'limpio', 'rápido', 'amable', 'genial',
            ],
            'en' => [
                'thank', 'good', 'great', 'excellent', 'very', 'place',
                'service', 'recommend', 'attention', 'price', 'quality',
                'bad', 'terrible', 'awful', 'amazing', 'staff',
                'clean', 'fast', 'friendly', 'awesome', 'experience',
            ],
            'fr' => [
                'merci', 'bien', 'excellent', 'très', 'endroit', 'service',
                'recommande', 'accueil', 'prix', 'qualité', 'mauvais',
                'horrible', 'terrible', 'incroyable', 'personnel',
                'propre', 'rapide', 'aimable', 'super', 'expérience',
            ],
            'pt' => [
                'obrigado', 'bom', 'excelente', 'muito', 'lugar', 'serviço',
                'recomendo', 'atenção', 'preço', 'qualidade', 'ruim',
                'horrível', 'péssimo', 'incrível', 'pessoal',
                'limpo', 'rápido', 'amável', 'ótimo', 'experiência',
            ],
            'de' => [
                'danke', 'gut', 'ausgezeichnet', 'sehr', 'ort', 'service',
                'empfehle', 'aufmerksamkeit', 'preis', 'qualität', 'schlecht',
                'schrecklich', 'furchtbar', 'unglaublich', 'personal',
                'sauber', 'schnell', 'freundlich', 'toll', 'erfahrung',
            ],
        ];
    }
}
