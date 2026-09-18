<?php

namespace Modules\Helpdesk\Services\AI;

class SentimentService
{
    public function __construct(
        private readonly AiClient $client,
        private readonly PromptSanitizer $sanitizer,
    ) {}

    /**
     * Detect sentiment of a text.
     *
     * @return array{label: string, score: float}
     */
    public function detect(string $text): array
    {
        if (! $this->client->isEnabled()) {
            return ['label' => 'neutral', 'score' => 0.0];
        }

        $trimmed = mb_substr(strip_tags($text), 0, 500);

        if (empty($trimmed)) {
            return ['label' => 'neutral', 'score' => 0.0];
        }

        $systemPrompt = 'Eres un analizador de sentimientos. '
            .'Responde SOLO con JSON válido en el formato: {"label":"positive|neutral|negative","score":-1.0..1.0}. '
            .'Sin texto adicional. '
            .$this->sanitizer->systemGuard();

        $safeText = $this->sanitizer->wrap($trimmed);

        $raw = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Analiza el sentimiento de este texto:\n{$safeText}"],
        ]);

        return $this->parseSentiment($raw);
    }

    /**
     * @return array{label: string, score: float}
     */
    private function parseSentiment(?string $raw): array
    {
        $default = ['label' => 'neutral', 'score' => 0.0];

        if (empty($raw)) {
            return $default;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (isset($decoded['label'], $decoded['score'])
                && in_array($decoded['label'], ['positive', 'neutral', 'negative'], true)
            ) {
                return [
                    'label' => $decoded['label'],
                    'score' => (float) $decoded['score'],
                ];
            }
        }

        return $default;
    }
}
