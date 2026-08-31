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
 *
 * Two entry points:
 *  - chat()          one-shot completion, no tools. The original behaviour.
 *  - chatWithTools() bounded tool-calling loop over a set of MCP tools; the
 *                    caller supplies the executor, so this service never knows
 *                    what a tool actually does. See the method docblock.
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
     *                                                                                                  `feature` tags the row in helpdesk_ai_usage (summary|classification|...).
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
     * Bounded tool-calling loop.
     *
     * The model may ask for tools; this service executes them through the
     * caller-supplied $executor and feeds the results back until the model
     * answers or the iteration ceiling is hit. It deliberately knows nothing
     * about what a tool does — McpToolBridge owns that, so the same tool
     * definition can also be served over the MCP server without duplication.
     *
     * Cost control is per iteration, not per call: the daily budget is checked
     * before EVERY provider request and each one gets its own helpdesk_ai_usage
     * row, so the real cost of a suggestion is visible instead of hidden behind
     * a single record.
     *
     * Degradation is deliberate rather than an error: with no tools, a provider
     * that cannot do tool-calling (gemini/local), or a budget that runs out
     * mid-loop, the caller still gets the best text produced so far.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array{name: string, description?: string, input_schema: array<string, mixed>}>  $tools
     *                                                                                                            Canonical (Anthropic-shaped) definitions; translated per provider below.
     * @param  callable(string $name, array<string, mixed> $arguments): mixed  $executor
     *                                                                                    Returns the tool result. Throwing is fine — it is caught and reported to the
     *                                                                                    model as a failed tool, which is usually recoverable.
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int, feature?: string, max_tool_iterations?: int}  $options
     * @return array{text: string|null, tool_calls: array<int, array{name: string, arguments: array<string, mixed>, ok: bool, error: string|null}>, iterations: int, tools_used: bool}|null
     */
    public function chatWithTools(array $messages, array $tools, callable $executor, array $options = []): ?array
    {
        $agent = $this->getDefaultAgent();

        if (! $agent) {
            return null;
        }

        $apiKey = $agent->getApiKey();

        if (empty($apiKey)) {
            return null;
        }

        // No tools to offer, or a provider without tool-calling: fall back to a
        // plain completion rather than failing. The answer is worse, not absent.
        if ($tools === [] || ! $this->providerSupportsTools($agent->provider)) {
            if ($tools !== []) {
                Log::info('AgentLlmService: provider has no tool-calling, running plain completion', [
                    'provider' => $agent->provider,
                ]);
            }

            $text = $this->chat($messages, $options);

            return $text === null
                ? null
                : ['text' => $text, 'tool_calls' => [], 'iterations' => 1, 'tools_used' => false];
        }

        $feature = (string) ($options['feature'] ?? 'other');
        $temperature = (float) ($options['temperature'] ?? 0.2);
        $maxTokens = (int) ($options['max_tokens'] ?? 1024);
        $timeout = (int) ($options['timeout'] ?? (int) config('helpdeskagents.mcp.timeout', 45));
        $maxIterations = max(1, (int) ($options['max_tool_iterations']
            ?? config('helpdeskagents.mcp.max_tool_iterations', 4)));

        $conversation = $messages;
        $executed = [];
        $lastText = null;
        $iterations = 0;

        for ($i = 0; $i < $maxIterations; $i++) {
            if ($this->usage->dailyBudgetExceeded()) {
                Log::notice('AgentLlmService: daily AI budget exceeded mid tool-loop', [
                    'provider' => $agent->provider,
                    'feature' => $feature,
                    'iteration' => $i,
                ]);

                break;
            }

            $iterations++;
            $startedAt = hrtime(true);

            try {
                $result = match ($agent->provider) {
                    'openai' => $this->callOpenAi($agent, $apiKey, $conversation, $temperature, $maxTokens, $timeout, $tools),
                    'anthropic' => $this->callAnthropic($agent, $apiKey, $conversation, $temperature, $maxTokens, $timeout, $tools),
                    default => $this->failure(0),
                };
            } catch (\Throwable $e) {
                Log::warning('AgentLlmService: tool-loop exception', [
                    'provider' => $agent->provider,
                    'model' => $agent->model,
                    'iteration' => $i,
                    'error' => $e->getMessage(),
                ]);

                $this->usage->record($agent->provider, $agent->model, $feature, null, null, $this->elapsedMs($startedAt), false);

                break;
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

            if (($result['status'] ?? null) !== null) {
                break;
            }

            $text = is_string($result['text'] ?? null) ? trim($result['text']) : '';

            if ($text !== '') {
                $lastText = $text;
            }

            $pending = $result['tool_calls'] ?? [];

            if ($pending === []) {
                break;
            }

            // Last allowed iteration: executing tools now would leave their
            // results with no turn to be answered in — pure wasted spend.
            if ($i === $maxIterations - 1) {
                Log::info('AgentLlmService: tool iteration ceiling reached, returning best text so far', [
                    'feature' => $feature,
                    'pending_tools' => array_column($pending, 'name'),
                ]);

                break;
            }

            $conversation[] = $this->assistantTurn($agent->provider, $result);

            $outcomes = [];

            foreach ($pending as $call) {
                $name = (string) ($call['name'] ?? '');
                $arguments = is_array($call['arguments'] ?? null) ? $call['arguments'] : [];

                try {
                    $output = $executor($name, $arguments);
                    $ok = true;
                    $error = null;
                } catch (\Throwable $e) {
                    // A failed tool is reported back to the model rather than
                    // aborting: it can often answer without that datum, or try
                    // a different tool.
                    $output = ['error' => $e->getMessage()];
                    $ok = false;
                    $error = $e->getMessage();

                    Log::warning('AgentLlmService: tool execution failed', [
                        'tool' => $name,
                        'error' => $e->getMessage(),
                    ]);
                }

                $executed[] = [
                    'name' => $name,
                    'arguments' => $arguments,
                    'ok' => $ok,
                    'error' => $error,
                ];

                $outcomes[] = [
                    'id' => (string) ($call['id'] ?? $name),
                    'name' => $name,
                    'content' => $this->encodeToolResult($output),
                    'ok' => $ok,
                ];
            }

            foreach ($this->toolResultTurns($agent->provider, $outcomes) as $turn) {
                $conversation[] = $turn;
            }
        }

        return [
            'text' => $lastText,
            'tool_calls' => $executed,
            'iterations' => $iterations,
            'tools_used' => $executed !== [],
        ];
    }

    public function providerSupportsTools(?string $provider): bool
    {
        if ($provider === null) {
            return false;
        }

        return (bool) config("helpdeskagents.providers.{$provider}.supports_tools", false);
    }

    /**
     * Is there a usable LLM at all? Lets callers skip building an expensive
     * context (ERP lookups, template shortlists) that would be thrown away.
     */
    public function isConfigured(): bool
    {
        $agent = $this->getDefaultAgent();

        return $agent !== null && ! empty($agent->getApiKey());
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $tools
     * @return array{text: ?string, tokens_in: ?int, tokens_out: ?int, status: ?int, tool_calls?: array<int, array<string, mixed>>, raw?: array<string, mixed>}
     */
    private function callOpenAi(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout, ?array $tools = null): array
    {
        $payload = [
            'model' => $agent->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn (array $t): array => [
                'type' => 'function',
                'function' => [
                    'name' => $t['name'],
                    'description' => (string) ($t['description'] ?? ''),
                    'parameters' => $t['input_schema'] ?? ['type' => 'object', 'properties' => (object) []],
                ],
            ], $tools);
        }

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->retry(2, 500, throw: false)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->failed()) {
            $this->logFailure('openai', $agent->model, $response->status());

            return $this->failure($response->status());
        }

        $message = $response->json('choices.0.message') ?? [];

        $toolCalls = [];

        foreach ($message['tool_calls'] ?? [] as $call) {
            if (($call['type'] ?? 'function') !== 'function') {
                continue;
            }

            // Arguments arrive as a JSON *string*; never string-match on it.
            $decoded = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);

            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => (string) ($call['function']['name'] ?? ''),
                'arguments' => is_array($decoded) ? $decoded : [],
            ];
        }

        return [
            'text' => $message['content'] ?? null,
            'tokens_in' => $this->intOrNull($response->json('usage.prompt_tokens')),
            'tokens_out' => $this->intOrNull($response->json('usage.completion_tokens')),
            'status' => null,
            'tool_calls' => $toolCalls,
            'raw' => $message,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $tools
     * @return array{text: ?string, tokens_in: ?int, tokens_out: ?int, status: ?int, tool_calls?: array<int, array<string, mixed>>, raw?: array<string, mixed>}
     */
    private function callAnthropic(AiAgent $agent, string $apiKey, array $messages, float $temperature, int $maxTokens, int $timeout, ?array $tools = null): array
    {
        $systemMessages = array_filter($messages, fn ($m) => $m['role'] === 'system');
        $chatMessages = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));
        $systemPrompt = implode("\n", array_map(
            fn ($m): string => is_string($m['content']) ? $m['content'] : '',
            $systemMessages
        ));

        $payload = [
            'model' => $agent->model,
            'messages' => $chatMessages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        if (trim($systemPrompt) !== '') {
            $payload['system'] = $systemPrompt;
        }

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn (array $t): array => [
                'name' => $t['name'],
                'description' => (string) ($t['description'] ?? ''),
                'input_schema' => $t['input_schema'] ?? ['type' => 'object', 'properties' => (object) []],
            ], $tools);
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

        $blocks = $response->json('content') ?? [];

        $text = '';
        $toolCalls = [];

        foreach ($blocks as $block) {
            // Un bloque cuenta como texto si lo dice su `type` o, a falta de
            // él, si simplemente trae `text`. La API real siempre manda el
            // tipo, pero exigirlo hace que cualquier variación de forma —una
            // versión distinta, un proxy que reescribe— devuelva null en
            // silencio en vez de la respuesta que sí venía.
            if (isset($block['text']) && ($block['type'] ?? 'text') === 'text') {
                $text .= $block['text'];
            }

            if (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = [
                    'id' => (string) ($block['id'] ?? ''),
                    'name' => (string) ($block['name'] ?? ''),
                    'arguments' => is_array($block['input'] ?? null) ? $block['input'] : [],
                ];
            }
        }

        return [
            'text' => $text !== '' ? $text : null,
            'tokens_in' => $this->intOrNull($response->json('usage.input_tokens')),
            'tokens_out' => $this->intOrNull($response->json('usage.output_tokens')),
            'status' => null,
            'tool_calls' => $toolCalls,
            'raw' => ['content' => $blocks],
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
     * The assistant turn to replay so the provider sees its own tool request.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function assistantTurn(string $provider, array $result): array
    {
        if ($provider === 'anthropic') {
            return [
                'role' => 'assistant',
                'content' => $result['raw']['content'] ?? [],
            ];
        }

        $message = $result['raw'] ?? [];

        return [
            'role' => 'assistant',
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
        ];
    }

    /**
     * Tool results, in the shape each provider expects.
     *
     * Anthropic wants ONE user turn holding every tool_result block; OpenAI
     * wants one `tool` message per call. Splitting Anthropic's blocks across
     * turns is what silently teaches a model to stop calling tools in parallel.
     *
     * @param  array<int, array{id: string, name: string, content: string, ok: bool}>  $outcomes
     * @return array<int, array<string, mixed>>
     */
    private function toolResultTurns(string $provider, array $outcomes): array
    {
        if ($provider === 'anthropic') {
            return [[
                'role' => 'user',
                'content' => array_map(fn (array $o): array => array_filter([
                    'type' => 'tool_result',
                    'tool_use_id' => $o['id'],
                    'content' => $o['content'],
                    'is_error' => $o['ok'] ? null : true,
                ], fn ($v) => $v !== null), $outcomes),
            ]];
        }

        return array_map(fn (array $o): array => [
            'role' => 'tool',
            'tool_call_id' => $o['id'],
            'content' => $o['content'],
        ], $outcomes);
    }

    /**
     * Tool output as text for the model, hard-capped in bytes.
     *
     * A customer's full order history can be tens of kilobytes; without this
     * cap one tool call could cost more than the rest of the conversation.
     */
    private function encodeToolResult(mixed $output): string
    {
        $text = is_string($output)
            ? $output
            : (json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $max = max(500, (int) config('helpdeskagents.mcp.max_result_bytes', 6000));

        if (strlen($text) <= $max) {
            return $text === '' ? '(sin resultado)' : $text;
        }

        return mb_strcut($text, 0, $max).' […resultado recortado]';
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
