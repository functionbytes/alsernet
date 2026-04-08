<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\AiAgent;
use Modules\Helpdesk\Models\AiAgentFlow;
use Modules\Helpdesk\Models\AiAgentFlowNode;
use Modules\Helpdesk\Models\AiAgentSession;
use Modules\Helpdesk\Models\AiAgentSessionMessage;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

class AiAgentFlowEngine
{
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

            $result = $this->executeNode($node, $session, $userMessage);
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

        return $this->callAiProvider($agent, $messages);
    }

    /**
     * Execute a 'condition' node — evaluates conditions and returns the matching next_node_id.
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
                'regex' => (bool) preg_match($value, $subject),
                default => false,
            };

            if ($matched) {
                return $nextNodeId;
            }
        }

        return $nodeData['default_next_node_id'] ?? null;
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
    private function callAiProvider(AiAgent $agent, array $messages): string
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

        return match ($provider) {
            'openai' => $this->callOpenAi($apiKey, $model, $messages, $temperature, $maxTokens),
            'anthropic' => $this->callAnthropic($apiKey, $model, $messages, $temperature, $maxTokens),
            'gemini' => $this->callGemini($apiKey, $model, $messages, $maxTokens),
            default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
        };
    }

    private function callOpenAi(
        ?string $apiKey,
        string $model,
        array $messages,
        float $temperature,
        int $maxTokens
    ): string {
        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('AI provider call failed: '.$response->status());
        }

        return $response->json('choices.0.message.content', '');
    }

    private function callAnthropic(
        ?string $apiKey,
        string $model,
        array $messages,
        float $temperature,
        int $maxTokens
    ): string {
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

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('AI provider call failed: '.$response->status());
        }

        return $response->json('content.0.text', '');
    }

    private function callGemini(?string $apiKey, string $model, array $messages, int $maxTokens): string
    {
        $contents = array_map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => array_values($contents),
                'generationConfig' => ['maxOutputTokens' => $maxTokens],
            ]
        );

        if ($response->failed()) {
            throw new \RuntimeException('AI provider call failed: '.$response->status());
        }

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

    private function assignTicket(mixed $ticket, array $nodeData): string
    {
        if (! isset($nodeData['agent_id'])) {
            return 'Action skipped: no agent_id configured';
        }

        $ticket->update(['assignee_id' => $nodeData['agent_id']]);

        return 'Ticket assigned to agent';
    }

    private function changeTicketStatus(mixed $ticket, array $nodeData): string
    {
        if (! isset($nodeData['status_id'])) {
            return 'Action skipped: no status_id configured';
        }

        $ticket->update(['status_id' => $nodeData['status_id']]);

        return 'Ticket status updated';
    }

    private function addTicketTag(mixed $ticket, array $nodeData): string
    {
        $tag = $nodeData['tag'] ?? '';

        if ($tag !== '') {
            $ticket->tags()->syncWithoutDetaching([$tag]);
        }

        return 'Tag added to ticket';
    }

    private function closeTicket(mixed $ticket): string
    {
        $ticket->update(['closed_at' => now()]);

        return 'Ticket closed';
    }
}
