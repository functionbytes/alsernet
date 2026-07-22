<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Models\AiAgent;

/**
 * Thin, fail-silent chat client over the LLM configured in the default AiAgent
 * (HelpdeskAgents settings). Used by the ticket-side AI enrichment jobs
 * (summaries, classification) which must NEVER break ticket flows: any
 * missing configuration, provider error or exception returns null.
 *
 * Unlike AiAgentFlowEngine (the conversational runtime with rate limits and
 * circuit breakers per session), this service is meant for short, one-shot
 * background completions dispatched from queued jobs.
 */
class AgentLlmService
{
    use InteractsWithDefaultAiAgent;

    private const DEFAULT_TIMEOUT = 20;

    private const DEFAULT_MAX_TOKENS = 512;

    /**
     * Run a one-shot chat completion against the configured default agent.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int}  $options
     * @return string|null Trimmed completion text, or null on any failure.
     */
    public function chat(array $messages, array $options = []): ?string
    {
        $agent = $this->getDefaultAgent();

        if (! $agent) {
            return null;
        }

        $apiKey = $agent->getApiKey();

        if (empty($apiKey)) {
            return null;
        }

        $temperature = (float) ($options['temperature'] ?? 0.2);
        $maxTokens = (int) ($options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS);
        $timeout = (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT);

        try {
            $text = match ($agent->provider) {
                'openai' => $this->callOpenAi($agent, $apiKey, $messages, $temperature, $maxTokens, $timeout),
                'anthropic' => $this->callAnthropic($agent, $apiKey, $messages, $temperature, $maxTokens, $timeout),
                'gemini' => $this->callGemini($agent, $apiKey, $messages, $maxTokens, $timeout),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('AgentLlmService: chat exception', [
                'provider' => $agent->provider,
                'model' => $agent->model,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($text === null && ! in_array($agent->provider, ['openai', 'anthropic', 'gemini'], true)) {
            Log::info('AgentLlmService: unsupported provider for background completions', [
                'provider' => $agent->provider,
            ]);
        }

        $text = is_string($text) ? trim($text) : null;

        return $text === '' ? null : $text;
    }

    private function callOpenAi(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout): ?string
    {
        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->retry(2, 500, throw: false)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $agent->model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        if ($response->failed()) {
            $this->logFailure('openai', $agent->model, $response->status());

            return null;
        }

        return $response->json('choices.0.message.content');
    }

    private function callAnthropic(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout): ?string
    {
        $systemMessages = array_filter($messages, fn ($m) => $m['role'] === 'system');
        $chatMessages = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));
        $systemPrompt = implode("\n", array_column($systemMessages, 'content'));

        $payload = [
            'model' => $agent->model,
            'messages' => $chatMessages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout($timeout)
            ->retry(2, 500, throw: false)
            ->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            $this->logFailure('anthropic', $agent->model, $response->status());

            return null;
        }

        return $response->json('content.0.text');
    }

    private function callGemini(AiAgent $agent, string $apiKey, array $messages, int $maxTokens, int $timeout): ?string
    {
        $contents = array_map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system')));

        // Misma decisión que AiAgentFlowEngine/LlmConnectionTesterService: la
        // key viaja en cabecera para no acabar en logs de acceso/proxies.
        $response = Http::timeout($timeout)
            ->retry(2, 500, throw: false)
            ->withHeader('x-goog-api-key', $apiKey)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$agent->model}:generateContent",
                [
                    'contents' => $contents,
                    'generationConfig' => ['maxOutputTokens' => $maxTokens],
                ]
            );

        if ($response->failed()) {
            $this->logFailure('gemini', $agent->model, $response->status());

            return null;
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    private function logFailure(string $provider, string $model, int $status): void
    {
        Log::warning('AgentLlmService: provider call failed', [
            'provider' => $provider,
            'model' => $model,
            'status' => $status,
        ]);
    }
}
