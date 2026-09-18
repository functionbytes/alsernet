<?php

namespace Modules\HelpdeskSocial\Services\Classifiers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Models\SocialComment;

class OpenAiIntentClassifier implements IntentClassifierInterface
{
    private ?string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('helpdesksocial.intent_classification.openai_api_key');
        $this->model = config('helpdesksocial.intent_classification.openai_model', 'gpt-4o-mini');
    }

    public function getIdentifier(): string
    {
        return 'openai';
    }

    public function classify(SocialComment $comment): array
    {
        return $this->classifyText($comment->body);
    }

    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    public function getConfidenceThreshold(): float
    {
        return config('helpdesksocial.intent_classification.confidence_threshold', 0.75);
    }

    /**
     * @return array<string, mixed>
     */
    public function classifyText(string $text): array
    {
        if (! $this->isAvailable()) {
            Log::warning('OpenAiIntentClassifier: API key not configured');

            return $this->fallback($text);
        }

        $cacheKey = 'social_intent_openai:'.md5($text);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($text) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a customer intent classifier. Analyze the text and return ONLY a JSON object with keys: intent (one of: query, complaint, purchase_interest, spam, positive, neutral), confidence (0.0-1.0), urgency (low, medium, high, critical), keywords_matched (array of strings). Do not include markdown or explanations.',
                            ],
                            ['role' => 'user', 'content' => $text],
                        ],
                        'temperature' => 0.1,
                        'max_tokens' => 200,
                        'response_format' => ['type' => 'json_object'],
                    ]);

                if ($response->failed()) {
                    Log::warning('OpenAiIntentClassifier: API request failed', [
                        'error' => $response->json(),
                    ]);

                    return $this->fallback($text);
                }

                $content = $response->json('choices.0.message.content');
                $result = json_decode($content, true);

                if (! is_array($result) || ! isset($result['intent'])) {
                    return $this->fallback($text);
                }

                return [
                    'intent' => $result['intent'],
                    'confidence' => (float) ($result['confidence'] ?? 0.5),
                    'classifier' => $this->getIdentifier(),
                    'urgency' => $result['urgency'] ?? 'low',
                    'keywords_matched' => $result['keywords_matched'] ?? [],
                    'raw_response' => $result,
                ];
            } catch (\Throwable $e) {
                Log::error('OpenAiIntentClassifier: Exception', ['error' => $e->getMessage()]);

                return $this->fallback($text);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function fallback(string $text): array
    {
        $rules = new RulesIntentClassifier;

        return $rules->classifyText($text);
    }
}
