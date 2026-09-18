<?php

namespace Modules\HelpdeskAgents\Services;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Services\CircuitBreaker;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Exceptions\LlmRateLimitException;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Modules\HelpdeskAgents\Models\AiAgentFlowNode;
use Modules\HelpdeskAgents\Models\AiAgentSession;
use Modules\HelpdeskAgents\Models\AiAgentSessionMessage;
use Modules\HelpdeskTickets\Models\Ticket;

class AiAgentFlowEngine
{
    public function __construct(
        private readonly PromptSanitizer $sanitizer,
    ) {}

    /**
     * Start a new session for a given agent + conversation + optional trigger message.
     */
    public function startSession(
        AiAgent $agent,
        Conversation $conversation,
        ?Customer $customer,
        string $triggerMessage = ''
    ): ?AiAgentSession {
        $flow = $agent->flows()->published()->latest()->first();

        if (! $flow) {
            Log::warning('AiAgentFlowEngine: no published flow found for agent', ['agent_id' => $agent->id]);

            return null;
        }

        $session = AiAgentSession::create([
            'ai_agent_id' => $agent->id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer?->id,
            'flow_id' => $flow->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        if ($triggerMessage !== '') {
            $this->processMessage($session, $triggerMessage);
        }

        return $session;
    }

    /**
     * Process a user message within an active session.
     * Advances the flow and returns the assistant's reply string (or null on failure).
     */
    public function processMessage(AiAgentSession $session, string $userMessage): ?string
    {
        try {
            // HD-003: sanitizar ANTES de persistir. Si se guarda el mensaje crudo,
            // buildHistoryWindow() lo reenvía al LLM sin filtrar en este turno y en
            // los siguientes, anulando el sanitizador (la copia final ya iba limpia,
            // pero la inyección se colaba por el historial).
            $sanitizedMessage = $this->sanitizer->sanitize(
                $userMessage,
                $session->customer?->id
            );

            AiAgentSessionMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $sanitizedMessage,
            ]);

            $flow = AiAgentFlow::with('flowNodes')->find($session->flow_id);

            if (! $flow) {
                $session->fail('Flow not found');

                return null;
            }

            $node = $this->resolveCurrentNode($session, $flow);

            if (! $node) {
                $session->fail('No starting node found');

                return null;
            }

            $result = $this->executeNode($node, $session, $sanitizedMessage);
            $output = $result['output'];
            $nextNodeId = $result['next_node_id'];

            AiAgentSessionMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $output,
                'node_id' => $node->node_id,
            ]);

            $session->update(['current_node_id' => $nextNodeId]);

            if ($nextNodeId === null) {
                $session->complete();
            } else {
                $nextNode = $flow->flowNodes->firstWhere('node_id', $nextNodeId);
                if ($nextNode && $nextNode->type === 'output') {
                    $session->complete();
                }
            }

            return $output;
        } catch (LlmRateLimitException $e) {
            Log::warning('AiAgentFlowEngine: rate limit exceeded', [
                'session_id' => $session->id,
                'limit_type' => $e->getLimitType(),
            ]);
            $session->fail($e->getMessage());

            return null;
        } catch (\Throwable $e) {
            Log::error('AiAgentFlowEngine: processMessage failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            $session->fail($e->getMessage());

            return null;
        }
    }

    /**
     * Execute a single flow node given the session context.
     *
     * @return array{output: string, next_node_id: string|null}
     */
    private function executeNode(
        AiAgentFlowNode $node,
        AiAgentSession $session,
        string $userInput
    ): array {
        $nodeData = $node->data ?? [];

        return match ($node->type) {
            'prompt' => [
                'output' => $this->executePromptNode($nodeData, $session, $userInput),
                'next_node_id' => $nodeData['next_node_id'] ?? null,
            ],
            'condition' => [
                'output' => '',
                'next_node_id' => $this->executeConditionNode($nodeData, $session, $userInput),
            ],
            'action' => [
                'output' => $this->executeActionNode($nodeData, $session),
                'next_node_id' => $nodeData['next_node_id'] ?? null,
            ],
            'input', 'output' => [
                'output' => $nodeData['message'] ?? '',
                'next_node_id' => $nodeData['next_node_id'] ?? null,
            ],
            default => [
                'output' => '',
                'next_node_id' => null,
            ],
        };
    }

    /**
     * Execute a 'prompt' node — calls the AI provider configured on the agent.
     */
    private function executePromptNode(array $nodeData, AiAgentSession $session, string $userInput): string
    {
        $systemPrompt = $nodeData['system_prompt'] ?? '';

        $history = $this->buildHistoryWindow($session);

        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($history as $message) {
            $messages[] = $message;
        }

        $messages[] = ['role' => 'user', 'content' => $userInput];

        $agent = $session->agent;

        return $this->callAiProvider($agent, $messages, $session);
    }

    /**
     * Sliding window of session history for the LLM: the N most-recent messages
     * (not the first N), re-sorted chronologically. Using `orderBy('id')` +
     * `limit` returned the OLDEST messages, so long sessions silently dropped
     * all recent context once they passed the window size.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function buildHistoryWindow(AiAgentSession $session): array
    {
        $windowSize = (int) config('helpdeskagents.history_window', 100);

        return $session->messages()
            ->orderByDesc('id')
            ->limit($windowSize)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (AiAgentSessionMessage $msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }

    /**
     * Execute a 'condition' node — evaluates conditions and returns the matching next_node_id.
     *
     * HD-004: regex patterns from node data are validated before use to prevent ReDoS and crashes.
     */
    private function executeConditionNode(array $nodeData, AiAgentSession $session, string $userInput): ?string
    {
        $conditions = $nodeData['conditions'] ?? [];

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? 'input';
            $operator = $condition['operator'] ?? 'contains';
            $value = $condition['value'] ?? '';
            $nextNodeId = $condition['next_node_id'] ?? null;

            $subject = match ($field) {
                'input' => $userInput,
                'previous_response' => $session->getContextValue('last_response', ''),
                'customer_name' => $session->customer?->name ?? '',
                'channel' => $session->conversation?->channel ?? '',
                default => $userInput,
            };

            $matched = match ($operator) {
                'contains' => str_contains(strtolower($subject), strtolower($value)),
                'equals' => strtolower($subject) === strtolower($value),
                'starts_with' => str_starts_with(strtolower($subject), strtolower($value)),
                'ends_with' => str_ends_with(strtolower($subject), strtolower($value)),
                'regex' => $this->safeRegexMatch($value, $subject),
                default => false,
            };

            if ($matched) {
                return $nextNodeId;
            }
        }

        return $nodeData['default_next_node_id'] ?? null;
    }

    /**
     * HD-004: safely execute preg_match with validation, backtrack limit, and error handling.
     */
    private function safeRegexMatch(string $pattern, string $subject): bool
    {
        // Validate pattern syntax before use
        if (@preg_match($pattern, '') === false) {
            Log::channel($this->resolveSecurityChannel())->warning('AiAgentFlowEngine: invalid regex pattern in condition node', [
                'pattern' => $pattern,
            ]);

            return false;
        }

        $previousLimit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1000000');

        try {
            $result = @preg_match($pattern, $subject);

            return $result === 1;
        } catch (\Throwable $e) {
            Log::warning('AiAgentFlowEngine: regex match error', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            ini_set('pcre.backtrack_limit', $previousLimit);
        }
    }

    /**
     * Execute an 'action' node — triggers a helpdesk action.
     */
    private function executeActionNode(array $nodeData, AiAgentSession $session): string
    {
        try {
            $actionType = $nodeData['action_type'] ?? '';
            $ticket = $session->conversation->tickets()->latest()->first();

            if (! $ticket) {
                return __('helpdeskagents::helpdeskagents.flow_results.ticket_not_found');
            }

            return match ($actionType) {
                'assign_ticket' => $this->assignTicket($ticket, $nodeData),
                'change_status' => $this->changeTicketStatus($ticket, $nodeData),
                'add_tag' => $this->addTicketTag($ticket, $nodeData),
                'send_email' => 'Email notification queued',
                'close_ticket' => $this->closeTicket($ticket),
                default => 'Unknown action type',
            };
        } catch (\Throwable $e) {
            Log::error('AiAgentFlowEngine: executeActionNode failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return __('helpdeskagents::helpdeskagents.flow_results.action_failed');
        }
    }

    /**
     * Call the appropriate AI provider based on agent configuration.
     */
    private function callAiProvider(AiAgent $agent, array $messages, AiAgentSession $session): string
    {
        $config = $agent->backups ?? [];
        $provider = $config['provider'] ?? $agent->provider;
        $model = $config['model'] ?? $agent->model;
        $apiKey = $agent->getApiKey();
        $temperature = (float) ($config['temperature'] ?? 0.7);
        $maxTokens = (int) ($config['max_tokens'] ?? 500);

        if (empty($apiKey)) {
            throw new \RuntimeException('AI provider API key is not configured for agent ID '.$agent->id);
        }

        $userId = $session->customer_id ?? $agent->id;

        return $this->executeWithRateLimit($userId, $session->id, function () use ($provider, $apiKey, $model, $messages, $temperature, $maxTokens) {
            return match ($provider) {
                'openai' => $this->callOpenAi($apiKey, $model, $messages, $temperature, $maxTokens),
                'anthropic' => $this->callAnthropic($apiKey, $model, $messages, $temperature, $maxTokens),
                'gemini' => $this->callGemini($apiKey, $model, $messages, $maxTokens),
                default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
            };
        });
    }

    /**
     * HD-002: enforce per-user and per-session rate limits before executing the LLM call.
     */
    private function executeWithRateLimit(int|string $userId, int|string $sessionId, Closure $call): string
    {
        // Namespace propio del módulo (config/config.php): es donde el operador
        // ajusta el control de gasto de LLM. Antes leía helpdesk.llm_rate_limits
        // (otro módulo), dejando el bloque de este módulo como config muerto.
        $perMinuteLimit = config('helpdeskagents.llm_rate_limits.per_user_per_minute', 10);
        $per5minLimit = config('helpdeskagents.llm_rate_limits.per_session_per_5min', 30);
        $perDayLimit = config('helpdeskagents.llm_rate_limits.per_user_per_day', 1000);

        $perMinuteKey = "llm:user:{$userId}:per_minute";
        $per5minKey = "llm:session:{$sessionId}:per_5min";
        $perDayKey = "llm:user:{$userId}:per_day";

        if (! RateLimiter::attempt($perMinuteKey, $perMinuteLimit, fn () => true, 60)) {
            Log::warning('AiAgentFlowEngine: per-minute rate limit exceeded', [
                'user_id' => $userId,
                'limit' => $perMinuteLimit,
            ]);
            throw new LlmRateLimitException('per_minute', 60);
        }

        if (! RateLimiter::attempt($per5minKey, $per5minLimit, fn () => true, 300)) {
            Log::warning('AiAgentFlowEngine: per-session 5-minute rate limit exceeded', [
                'session_id' => $sessionId,
                'limit' => $per5minLimit,
            ]);
            throw new LlmRateLimitException('per_5min', 300);
        }

        if (! RateLimiter::attempt($perDayKey, $perDayLimit, fn () => true, 86400)) {
            Log::warning('AiAgentFlowEngine: daily rate limit exceeded', [
                'user_id' => $userId,
                'limit' => $perDayLimit,
            ]);
            throw new LlmRateLimitException('per_day', 86400);
        }

        return $call();
    }

    /**
     * HD-006: call OpenAI with timeout, retry, circuit breaker, and structured logging.
     */
    private function callOpenAi(
        ?string $apiKey,
        string $model,
        array $messages,
        float $temperature,
        int $maxTokens
    ): string {
        $circuit = new CircuitBreaker('llm:openai');

        if ($circuit->isOpen()) {
            throw new \RuntimeException('OpenAI provider is temporarily unavailable (circuit open).');
        }

        $startedAt = hrtime(true);

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        if ($response->failed()) {
            $circuit->recordFailure();
            Log::channel('helpdesk')->error('AiAgentFlowEngine: OpenAI call failed', [
                'provider' => 'openai',
                'model' => $model,
                'status' => $response->status(),
                'duration_ms' => $durationMs,
            ]);
            $this->recordUsage('openai', $model, null, null, $durationMs, false, $response->status());
            throw new \RuntimeException("OpenAI call failed with status {$response->status()}.");
        }

        $circuit->recordSuccess();

        $tokensUsed = $response->json('usage.total_tokens');

        Log::channel('helpdesk')->info('AiAgentFlowEngine: OpenAI call completed', [
            'provider' => 'openai',
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens_used' => $tokensUsed,
        ]);

        $this->recordUsage(
            'openai',
            $model,
            $response->json('usage.prompt_tokens'),
            $response->json('usage.completion_tokens'),
            $durationMs,
            true
        );

        return $response->json('choices.0.message.content', '');
    }

    /**
     * HD-006: call Anthropic with timeout, retry, circuit breaker, and structured logging.
     */
    private function callAnthropic(
        ?string $apiKey,
        string $model,
        array $messages,
        float $temperature,
        int $maxTokens
    ): string {
        $circuit = new CircuitBreaker('llm:anthropic');

        if ($circuit->isOpen()) {
            throw new \RuntimeException('Anthropic provider is temporarily unavailable (circuit open).');
        }

        $systemMessages = array_filter($messages, fn ($m) => $m['role'] === 'system');
        $chatMessages = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));
        $systemPrompt = implode("\n", array_column($systemMessages, 'content'));

        $payload = [
            'model' => $model,
            'messages' => $chatMessages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        $startedAt = hrtime(true);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->post('https://api.anthropic.com/v1/messages', $payload);

        $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        if ($response->failed()) {
            $circuit->recordFailure();
            Log::channel('helpdesk')->error('AiAgentFlowEngine: Anthropic call failed', [
                'provider' => 'anthropic',
                'model' => $model,
                'status' => $response->status(),
                'duration_ms' => $durationMs,
            ]);
            $this->recordUsage('anthropic', $model, null, null, $durationMs, false, $response->status());
            throw new \RuntimeException("Anthropic call failed with status {$response->status()}.");
        }

        $circuit->recordSuccess();

        $tokensUsed = $response->json('usage.input_tokens', 0) + $response->json('usage.output_tokens', 0);

        Log::channel('helpdesk')->info('AiAgentFlowEngine: Anthropic call completed', [
            'provider' => 'anthropic',
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens_used' => $tokensUsed ?: null,
        ]);

        $this->recordUsage(
            'anthropic',
            $model,
            $response->json('usage.input_tokens'),
            $response->json('usage.output_tokens'),
            $durationMs,
            true
        );

        return $response->json('content.0.text', '');
    }

    /**
     * HD-006: call Gemini with timeout, retry, circuit breaker, and structured logging.
     */
    private function callGemini(?string $apiKey, string $model, array $messages, int $maxTokens): string
    {
        $circuit = new CircuitBreaker('llm:gemini');

        if ($circuit->isOpen()) {
            throw new \RuntimeException('Gemini provider is temporarily unavailable (circuit open).');
        }

        $contents = array_map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        $startedAt = hrtime(true);

        // La key va por cabecera (no en el query string) para que no acabe en
        // logs de acceso/proxies; mismo criterio que LlmConnectionTesterService.
        $response = Http::timeout(30)
            ->retry(2, 500, throw: false)
            ->withHeader('x-goog-api-key', (string) $apiKey)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => array_values($contents),
                    'generationConfig' => ['maxOutputTokens' => $maxTokens],
                ]
            );

        $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        if ($response->failed()) {
            $circuit->recordFailure();
            Log::channel('helpdesk')->error('AiAgentFlowEngine: Gemini call failed', [
                'provider' => 'gemini',
                'model' => $model,
                'status' => $response->status(),
                'duration_ms' => $durationMs,
            ]);
            $this->recordUsage('gemini', $model, null, null, $durationMs, false, $response->status());
            throw new \RuntimeException("Gemini call failed with status {$response->status()}.");
        }

        $circuit->recordSuccess();

        Log::channel('helpdesk')->info('AiAgentFlowEngine: Gemini call completed', [
            'provider' => 'gemini',
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens_used' => null, // Gemini v1beta does not expose token usage in this endpoint
        ]);

        $this->recordUsage(
            'gemini',
            $model,
            $response->json('usageMetadata.promptTokenCount'),
            $response->json('usageMetadata.candidatesTokenCount'),
            $durationMs,
            true
        );

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    /**
     * Observabilidad de coste: cada llamada del runtime conversacional queda
     * en helpdesk_ai_usage con feature "chatflow". Fail-silent — el ledger
     * jamás rompe una conversación.
     */
    private function recordUsage(
        string $provider,
        string $model,
        mixed $tokensIn,
        mixed $tokensOut,
        int $durationMs,
        bool $success,
        ?int $statusCode = null
    ): void {
        try {
            app(AiUsageRecorder::class)->record(
                $provider,
                $model,
                'chatflow',
                is_numeric($tokensIn) ? (int) $tokensIn : null,
                is_numeric($tokensOut) ? (int) $tokensOut : null,
                $durationMs,
                $success,
                $statusCode
            );
        } catch (\Throwable) {
            // never let usage accounting break the conversation
        }
    }

    /**
     * Resolve the current flow node for the session.
     */
    private function resolveCurrentNode(AiAgentSession $session, AiAgentFlow $flow): ?AiAgentFlowNode
    {
        if ($session->current_node_id) {
            return $flow->flowNodes->firstWhere('node_id', $session->current_node_id);
        }

        $startingNodeData = $flow->getStartingNode();

        if (! $startingNodeData) {
            return null;
        }

        return $flow->flowNodes->firstWhere('node_id', $startingNodeData['id'] ?? null);
    }

    private function assignTicket(Ticket $ticket, array $nodeData): string
    {
        if (! isset($nodeData['agent_id'])) {
            return __('helpdeskagents::helpdeskagents.flow_results.skipped_no_agent');
        }

        $ticket->update(['assignee_id' => $nodeData['agent_id']]);

        return __('helpdeskagents::helpdeskagents.flow_results.ticket_assigned');
    }

    private function changeTicketStatus(Ticket $ticket, array $nodeData): string
    {
        if (! isset($nodeData['status_id'])) {
            return __('helpdeskagents::helpdeskagents.flow_results.skipped_no_status');
        }

        $ticket->update(['status_id' => $nodeData['status_id']]);

        return __('helpdeskagents::helpdeskagents.flow_results.ticket_status_updated');
    }

    private function addTicketTag(Ticket $ticket, array $nodeData): string
    {
        $tag = $nodeData['tag'] ?? '';

        // `tags` es una columna JSON (cast array), no una relación: hay que
        // añadir al array, no invocar un método de relación inexistente
        // (`$ticket->tags()` daría "Call to undefined method").
        if ($tag !== '') {
            $tags = $ticket->tags ?? [];
            if (! in_array($tag, $tags, true)) {
                $tags[] = $tag;
                $ticket->update(['tags' => $tags]);
            }
        }

        return __('helpdeskagents::helpdeskagents.flow_results.tag_added');
    }

    private function closeTicket(Ticket $ticket): string
    {
        $ticket->update(['closed_at' => now()]);

        return __('helpdeskagents::helpdeskagents.flow_results.ticket_closed');
    }

    private function resolveSecurityChannel(): string
    {
        $channels = config('logging.channels', []);

        return isset($channels['security']) ? 'security' : config('logging.default', 'stack');
    }
}
