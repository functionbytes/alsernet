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
 *
 * Observability: every outgoing call is recorded in helpdesk_ai_usage via
 * AiUsageRecorder (provider, model, feature, tokens in/out when the provider
 * returns them, duration, success). A configurable daily budget
 * (helpdeskagents.ai_usage.daily_max_calls / daily_max_tokens) makes chat()
 * return null with a log entry once exceeded — fail-silent, like everything
 * else here.
 */
class AgentLlmService
{
    use InteractsWithDefaultAiAgent;

    private const DEFAULT_TIMEOUT = 20;

    private const DEFAULT_MAX_TOKENS = 512;

    public function __construct(
        private readonly AiUsageRecorder $usage,
    ) {}

    /**
     * Run a one-shot chat completion against the configured default agent.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int, feature?: string}  $options
     *                                                                                                 `feature` tags the row in helpdesk_ai_usage (summary|classification|...).
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

        $feature = (string) ($options['feature'] ?? 'other');

        if ($this->usage->dailyBudgetExceeded()) {
            Log::notice('AgentLlmService: daily AI budget exceeded, skipping call', [
                'provider' => $agent->provider,
                'model' => $agent->model,
                'feature' => $feature,
            ]);

            return null;
        }

        $temperature = (float) ($options['temperature'] ?? 0.2);
        $maxTokens = (int) ($options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS);
        $timeout = (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT);

        $startedAt = hrtime(true);

        try {
            $result = match ($agent->provider) {
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

            $this->usage->record(
                $agent->provider,
                $agent->model,
                $feature,
                null,
                null,
                $this->elapsedMs($startedAt),
                false
            );

            return null;
        }

        if ($result === null && ! in_array($agent->provider, ['openai', 'anthropic', 'gemini'], true)) {
            Log::info('AgentLlmService: unsupported provider for background completions', [
                'provider' => $agent->provider,
            ]);

            // Unsupported provider: no HTTP call happened, nothing to record.
            return null;
        }

        $this->usage->record(
            $agent->provider,
            $agent->model,
            $feature,
            $result['tokens_in'] ?? null,
            $result['tokens_out'] ?? null,
            $this->elapsedMs($startedAt),
            ($result['status'] ?? null) === null,
            $result['status'] ?? null
        );

        $text = is_string($result['text'] ?? null) ? trim($result['text']) : null;

        return $text === '' ? null : $text;
    }

    /**
     * @return array{text: ?string, tokens_in: ?int, tokens_out: ?int, status: ?int}
     */
    private function callOpenAi(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout): array
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

            return $this->failure($response->status());
        }

        return [
            'text' => $response->json('choices.0.message.content'),
            'tokens_in' => $this->intOrNull($response->json('usage.prompt_tokens')),
            'tokens_out' => $this->intOrNull($response->json('usage.completion_tokens')),
            'status' => null,
        ];
    }

    /**
     * @return array{text: ?string, tokens_in: ?int, tokens_out: ?int, status: ?int}
     */
    private function callAnthropic(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout): array
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

            return $this->failure($response->status());
        }

        return [
            'text' => $response->json('content.0.text'),
            'tokens_in' => $this->intOrNull($response->json('usage.input_tokens')),
            'tokens_out' => $this->intOrNull($response->json('usage.output_tokens')),
            'status' => null,
        ];
    }

    /**
     * @return array{text: ?string, tokens_in: ?int, tokens_out: ?int, status: ?int}
     */
    private function callGemini(AiAgent $agent, string $apiKey, array $messages, int $maxTokens, int $timeout): array
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

            return $this->failure($response->status());
        }

        return [
            'text' => $response->json('candidates.0.content.parts.0.text'),
            'tokens_in' => $this->intOrNull($response->json('usageMetadata.promptTokenCount')),
            'tokens_out' => $this->intOrNull($response->json('usageMetadata.candidatesTokenCount')),
            'status' => null,
        ];
    }

    /**
     * @return array{text: null, tokens_in: null, tokens_out: null, status: int}
     */
    private function failure(int $status): array
    {
        return ['text' => null, 'tokens_in' => null, 'tokens_out' => null, 'status' => $status];
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function elapsedMs(int|float $startedAtNs): int
    {
        return (int) ((hrtime(true) - $startedAtNs) / 1_000_000);
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
