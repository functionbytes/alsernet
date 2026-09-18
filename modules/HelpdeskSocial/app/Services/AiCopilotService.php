<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class AiCopilotService
{
    private ?string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('helpdesksocial.intent_classification.openai_api_key');
        $this->model = config('helpdesksocial.intent_classification.openai_model', 'gpt-4o-mini');
    }

    /**
     * Suggest a reply for a social comment using AI.
     *
     * @return array<string, mixed>
     */
    public function suggestReply(SocialComment $comment): array
    {
        $templates = $this->loadTemplatesForContext($comment);

        return $this->suggestReplyText(
            $comment->body,
            $comment->platform,
            $comment->intent,
            $comment->sentiment['label'] ?? 'neutral',
            $templates
        );
    }

    /**
     * Suggest a reply from raw context.
     *
     * @param  array<int, array<string, mixed>>  $templates
     * @return array<string, mixed>
     */
    public function suggestReplyText(string $commentBody, string $platform, ?string $intent, string $sentiment, array $templates = []): array
    {
        if (! $this->isAvailable()) {
            return $this->fallbackSuggestion($templates);
        }

        try {
            $templateHints = '';
            if (! empty($templates)) {
                $templateHints = "Available templates:\n";
                foreach ($templates as $t) {
                    $templateHints .= "- {$t['name']}: {$t['body']}\n";
                }
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "You are a helpful customer support assistant. Respond in the same language as the customer. Be concise, polite, and professional. Platform: {$platform}. Intent: {$intent}. Sentiment: {$sentiment}.\n{$templateHints}\nReturn ONLY a JSON object with keys: reply (string), confidence (0.0-1.0). Do not include markdown or explanations.",
                        ],
                        ['role' => 'user', 'content' => $commentBody],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 300,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::warning('AiCopilotService: OpenAI request failed', [
                    'error' => $response->json(),
                ]);

                return $this->fallbackSuggestion($templates);
            }

            $content = $response->json('choices.0.message.content');
            $result = json_decode($content, true);

            if (! is_array($result) || ! isset($result['reply'])) {
                return $this->fallbackSuggestion($templates);
            }

            return [
                'reply' => $result['reply'],
                'confidence' => (float) ($result['confidence'] ?? 0.7),
                'source' => 'openai',
            ];
        } catch (\Throwable $e) {
            Log::error('AiCopilotService: exception', ['error' => $e->getMessage()]);

            return $this->fallbackSuggestion($templates);
        }
    }

    /**
     * Check if the OpenAI API is available.
     */
    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTemplatesForContext(SocialComment $comment): array
    {
        return SocialTemplate::active()
            ->forPlatform($comment->platform)
            ->limit(5)
            ->get()
            ->map(fn (SocialTemplate $t) => [
                'name' => $t->name,
                'body' => $t->body,
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $templates
     * @return array<string, mixed>
     */
    private function fallbackSuggestion(array $templates): array
    {
        if (! empty($templates)) {
            $first = $templates[0];

            return [
                'reply' => $first['body'],
                'confidence' => 0.5,
                'source' => 'template_fallback',
            ];
        }

        return [
            'reply' => 'Gracias por contactarnos. Un agente le respondera en breve.',
            'confidence' => 0.3,
            'source' => 'generic_fallback',
        ];
    }
}
