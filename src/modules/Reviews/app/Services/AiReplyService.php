<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAiSetting;
use Modules\Reviews\Models\ReviewReply;

class AiReplyService
{
    private const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';

    private const ANTHROPIC_MODEL = '.claude-sonnet-4-6';

    /** @var array<string, string> */
    private const TONE_INSTRUCTIONS = [
        'professional' => 'Respond formally and professionally',
        'friendly' => 'Use a warm, friendly, conversational tone',
        'apologetic' => 'Express sincere apology and empathy',
        'grateful' => 'Express genuine gratitude and appreciation',
        'concise' => 'Keep the response brief, under 100 words',
    ];

    /**
     * Generate 3 AI reply suggestions for a review using the configured Anthropic API key.
     *
     * Falls back to static templates when the key is not configured or the API call fails.
     *
     * @return array<int, array{title: string, text: string}>
     */
    public function suggestReply(Review $review, string $language = 'es', string $tone = 'professional'): array
    {
        $apiKey = config('services.anthropic.key', '');

        if (empty($apiKey)) {
            return $this->fallbackSuggestions($review);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post(self::ANTHROPIC_API_URL, [
                'model' => self::ANTHROPIC_MODEL,
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $this->buildSuggestionsPrompt($review, $language, $tone)],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('Anthropic API error on suggestReply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackSuggestions($review);
            }

            return $this->parseSuggestions($response->json('content.0.text', ''), $review);
        } catch (\Throwable $e) {
            Log::error('AiReplyService::suggestReply error: '.$e->getMessage());

            return $this->fallbackSuggestions($review);
        }
    }

    /**
     * Whether the direct Anthropic API key (services.anthropic.key) is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key', ''));
    }

    private function buildSuggestionsPrompt(Review $review, string $language, string $tone = 'professional'): string
    {
        $rating = $review->star_rating?->value() ?? 0;
        $comment = $review->comment ?? '';
        $reviewer = $review->reviewer_name ?? 'el cliente';
        $businessName = setting('site_name', 'nuestro negocio');

        $defaultTone = match (true) {
            $rating >= 4 => 'agradecida y cálida',
            $rating === 3 => 'empática y constructiva',
            default => 'empática, disculpándose y ofreciendo solución',
        };

        $toneInstruction = self::TONE_INSTRUCTIONS[$tone] ?? $defaultTone;

        return <<<PROMPT
Eres el gestor de reputación online de "{$businessName}".

Genera 3 variaciones de respuesta profesional a esta reseña de Google.

RESEÑA:
- Autor: {$reviewer}
- Calificación: {$rating}/5 estrellas
- Comentario: "{$comment}"

INSTRUCCIONES:
- Idioma: {$language}
- Tono: {$toneInstruction}
- Cada respuesta debe ser entre 2-4 oraciones
- Menciona el nombre del autor al inicio
- No incluyas información inventada sobre pedidos o fechas específicas
- Responde en formato JSON con esta estructura exacta:

{
  "suggestions": [
    {"title": "Respuesta formal", "text": "..."},
    {"title": "Respuesta cercana", "text": "..."},
    {"title": "Respuesta breve", "text": "..."}
  ]
}

Devuelve SOLO el JSON, sin texto adicional.
PROMPT;
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    private function parseSuggestions(string $content, Review $review): array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', trim($content));
        $parsed = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['suggestions'])) {
            return array_map(function (array $suggestion) {
                $suggestion['text'] = $this->truncateToGoogleLimit($suggestion['text'] ?? '');

                return $suggestion;
            }, $parsed['suggestions']);
        }

        return $this->fallbackSuggestions($review);
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    private function fallbackSuggestions(Review $review): array
    {
        $rating = $review->star_rating?->value() ?? 0;
        $name = $review->reviewer_name ?? 'estimado cliente';

        if ($rating >= 4) {
            return [
                ['title' => 'Respuesta de agradecimiento', 'text' => "¡Gracias, {$name}! Nos alegra mucho saber que tuviste una experiencia positiva. Tu opinión nos motiva a seguir mejorando cada día."],
                ['title' => 'Respuesta cercana', 'text' => "Muchas gracias por tu reseña, {$name}. Es un placer para nosotros saber que quedaste satisfecho/a. ¡Esperamos verte pronto!"],
                ['title' => 'Respuesta breve', 'text' => "¡Gracias, {$name}! Apreciamos mucho tu valoración."],
            ];
        }

        return [
            ['title' => 'Respuesta empática', 'text' => "Lamentamos que tu experiencia no haya sido la esperada, {$name}. Nos tomamos muy en serio tu opinión y trabajaremos para mejorar."],
            ['title' => 'Respuesta con solución', 'text' => "Muchas gracias por tu honesta opinión, {$name}. Pedimos disculpas por los inconvenientes. Por favor contáctanos directamente para que podamos resolver tu situación."],
            ['title' => 'Respuesta formal', 'text' => "Gracias por compartir tu experiencia, {$name}. Lamentamos no haber cumplido tus expectativas. Tu feedback es muy valioso para seguir mejorando."],
        ];
    }

    public function generate(Review $review, ReviewAiSetting $settings, string $toneOverride = ''): string
    {
        $prompt = $this->buildPrompt($review, $settings, $toneOverride);

        $text = match ($settings->provider) {
            'anthropic' => $this->callAnthropic($prompt, $settings),
            'openai' => $this->callOpenAi($prompt, $settings),
            default => throw new \InvalidArgumentException("Proveedor no soportado: {$settings->provider}"),
        };

        return $this->truncateToGoogleLimit($text);
    }

    /**
     * Truncate text to Google's reply limit at the last complete sentence boundary.
     */
    private function truncateToGoogleLimit(string $text): string
    {
        if (mb_strlen($text) <= ReviewReply::GOOGLE_MAX_CHARS) {
            return $text;
        }

        $truncated = mb_substr($text, 0, ReviewReply::GOOGLE_MAX_CHARS);

        // Try to break at last sentence-ending punctuation
        $lastSentenceEnd = max(
            mb_strrpos($truncated, '. ') ?: 0,
            mb_strrpos($truncated, '! ') ?: 0,
            mb_strrpos($truncated, '? ') ?: 0,
        );

        if ($lastSentenceEnd > 0) {
            // Include the punctuation mark itself (+1)
            $truncated = mb_substr($truncated, 0, $lastSentenceEnd + 1);
        }

        return trim($truncated).' (truncado a 4096 caracteres)';
    }

    public function testConnection(ReviewAiSetting $settings): bool
    {
        try {
            return match ($settings->provider) {
                'anthropic' => $this->testAnthropic($settings),
                'openai' => $this->testOpenAi($settings),
                default => false,
            };
        } catch (\Throwable) {
            return false;
        }
    }

    private function buildPrompt(Review $review, ReviewAiSetting $settings, string $toneOverride = ''): string
    {
        $toneMap = [
            'professional' => 'profesional y cortés',
            'friendly' => 'amigable y cercano',
            'apologetic' => 'empático, disculpándose sinceramente',
            'grateful' => 'agradecido y apreciativo',
            'concise' => 'conciso, menos de 100 palabras',
            'formal' => 'formal y empresarial',
            'casual' => 'casual y directo',
        ];

        $languageMap = [
            'es' => 'español',
            'en' => 'inglés',
            'pt' => 'portugués',
            'fr' => 'francés',
        ];

        $effectiveTone = $toneOverride !== '' ? $toneOverride : $settings->tone;
        $toneLabel = $toneMap[$effectiveTone] ?? 'profesional';
        $languageLabel = $languageMap[$settings->language] ?? $settings->language;
        $starRating = $review->star_rating?->value() ?? '?';
        $comment = $review->comment
            ? "\nComentario: \"{$review->comment}\""
            : "\n(La reseña no incluye comentario escrito)";
        $locationName = $review->location?->name ?? '';
        $customInstructions = $settings->custom_instructions
            ? "\nInstrucciones adicionales: {$settings->custom_instructions}"
            : '';

        return <<<PROMPT
Eres el representante de servicio al cliente de "{$locationName}". Debes responder a la siguiente reseña de Google de forma {$toneLabel}.

Datos de la reseña:
- Autor: {$review->reviewer_name}
- Calificación: {$starRating} de 5 estrellas{$comment}

Responde en {$languageLabel}. Máximo {$settings->max_tokens} tokens en tu respuesta. No incluyas saludos genéricos ni frases vacías. Ve directo a responder la reseña de forma auténtica y específica.{$customInstructions}

Escribe únicamente el texto de la respuesta, sin comillas ni prefijos.
PROMPT;
    }

    private function callAnthropic(string $prompt, ReviewAiSetting $settings): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $settings->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $settings->model,
                'max_tokens' => $settings->max_tokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Error de Anthropic: {$error}");
        }

        return trim($response->json('content.0.text') ?? '');
    }

    private function callOpenAi(string $prompt, ReviewAiSetting $settings): string
    {
        $response = Http::timeout(30)
            ->withToken($settings->api_key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $settings->model,
                'max_tokens' => $settings->max_tokens,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente de servicio al cliente que responde reseñas de Google de forma profesional.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Error de OpenAI: {$error}");
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    private function testAnthropic(ReviewAiSetting $settings): bool
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $settings->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $settings->model,
                'max_tokens' => 5,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hi'],
                ],
            ]);

        return $response->successful();
    }

    private function testOpenAi(ReviewAiSetting $settings): bool
    {
        $response = Http::timeout(15)
            ->withToken($settings->api_key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $settings->model,
                'max_tokens' => 5,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hi'],
                ],
            ]);

        return $response->successful();
    }
}
