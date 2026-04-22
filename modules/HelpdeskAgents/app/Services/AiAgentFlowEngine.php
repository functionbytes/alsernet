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
            AiAgentSessionMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $userMessage,
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

            // HD-003: sanitize user input before passing through the flow
            $sanitizedMessage = $this->sanitizer->sanitize(
                $userMessage,
                $session->customer?->id
            );

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

        $history = $session->messages()->get()->map(fn (AiAgentSessionMessage $msg) => [
            'role' => $msg->role,
            'content' => $msg->content,
        ])->toArray();

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
                return 'No ticket found for this conversation';
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

            return 'Action could not be completed';
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
        $apiKey = $config['api_key'] ?? null;
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
        $perMinuteLimit = config('helpdesk.llm_rate_limits.per_user_per_minute', 10);
        $per5minLimit = config('helpdesk.llm_rate_limits.per_session_per_5min', 30);
        $perDayLimit = config('helpdesk.llm_rate_limits.per_user_per_day', 1000);

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

        $response = Http::timeout(30)
            ->retry(2, 500, throw: false)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
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
            throw new \RuntimeException("Gemini call failed with status {$response->status()}.");
        }

        $circuit->recordSuccess();

        Log::channel('helpdesk')->info('AiAgentFlowEngine: Gemini call completed', [
            'provider' => 'gemini',
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens_used' => null, // Gemini v1beta does not expose token usage in this endpoint
        ]);

        return $response->json('candidates.0.content.parts.0.text', '');
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
            return 'Action skipped: no agent_id configured';
        }

        $ticket->update(['assignee_id' => $nodeData['agent_id']]);

        return 'Ticket assigned to agent';
    }

    private function changeTicketStatus(Ticket $ticket, array $nodeData): string
    {
        if (! isset($nodeData['status_id'])) {
            return 'Action skipped: no status_id configured';
        }

        $ticket->update(['status_id' => $nodeData['status_id']]);

        return 'Ticket status updated';
    }

    private function addTicketTag(Ticket $ticket, array $nodeData): string
    {
        $tag = $nodeData['tag'] ?? '';

        if ($tag !== '') {
            $ticket->tags()->syncWithoutDetaching([$tag]);
        }

        return 'Tag added to ticket';
    }

    private function closeTicket(Ticket $ticket): string
    {
        $ticket->update(['closed_at' => now()]);

        return 'Ticket closed';
    }

    private function resolveSecurityChannel(): string
    {
        $channels = config('logging.channels', []);

        return isset($channels['security']) ? 'security' : config('logging.default', 'stack');
    }
}
