<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;

class SentimentAnalysisService
{
    private ?string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('helpdesksocial.intent_classification.openai_api_key');
        $this->model = config('helpdesksocial.intent_classification.openai_model', 'gpt-4o-mini');
    }

    /**
     * Analyze the sentiment of a social comment and persist the result.
     *
     * @return array<string, mixed>
     */
    public function analyze(SocialComment $comment): array
    {
        $result = $this->analyzeText($comment->body);

        $comment->update(['sentiment' => $result]);

        return $result;
    }

    /**
     * Analyze raw text sentiment.
     *
     * @return array<string, mixed>
     */
    public function analyzeText(string $text): array
    {
        if ($this->apiKey && $this->apiKey !== '') {
            $openAiResult = $this->analyzeWithOpenAi($text);

            if ($openAiResult !== null) {
                return $openAiResult;
            }
        }

        return $this->analyzeWithRules($text);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeWithOpenAi(string $text): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a sentiment analyzer. Analyze the text and return ONLY a JSON object with keys: label (one of: positive, negative, neutral, mixed), score (0.0-1.0 confidence). Do not include markdown or explanations.',
                        ],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 100,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::warning('SentimentAnalysisService: OpenAI request failed', [
                    'error' => $response->json(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');
            $result = json_decode($content, true);

            if (! is_array($result) || ! isset($result['label'])) {
                return null;
            }

            return [
                'label' => $result['label'],
                'score' => (float) ($result['score'] ?? 0.5),
                'source' => 'openai',
            ];
        } catch (\Throwable $e) {
            Log::error('SentimentAnalysisService: OpenAI exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeWithRules(string $text): array
    {
        $lower = mb_strtolower($text);

        $positiveWords = ['excelente', 'genial', 'fantastico', 'maravilloso', 'me encanta', 'gracias', 'perfecto', 'buen', 'buena', 'bueno', 'mejor', 'feliz', 'satisfecho', 'recomiendo', 'love', 'great', 'awesome', 'thank', 'good', 'best', 'happy', 'amazing'];
        $negativeWords = ['malo', 'pesimo', 'terrible', 'horrible', 'odio', 'nunca', 'peor', 'decepcionado', 'fracaso', 'error', 'problema', 'falla', 'queja', 'reclamo', 'bad', 'worst', 'hate', 'terrible', 'awful', 'disappointed', 'error', 'problem', 'fail', 'complaint', 'urgente', 'urgent'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($lower, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($lower, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > 0 && $negativeCount > 0) {
            return [
                'label' => 'mixed',
                'score' => 0.5,
                'source' => 'rules',
            ];
        }

        if ($positiveCount > 0) {
            return [
                'label' => 'positive',
                'score' => min(0.95, 0.6 + ($positiveCount * 0.1)),
                'source' => 'rules',
            ];
        }

        if ($negativeCount > 0) {
            return [
                'label' => 'negative',
                'score' => min(0.95, 0.6 + ($negativeCount * 0.1)),
                'source' => 'rules',
            ];
        }

        return [
            'label' => 'neutral',
            'score' => 0.5,
            'source' => 'rules',
        ];
    }
}
