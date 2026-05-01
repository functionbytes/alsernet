<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\AiAgentEscalated;
use Modules\Helpdesk\Models\AiAgent;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\HelpCenterArticle;

class AiAgentService
{
    public function __construct(
        private readonly AiClient $client
    ) {}

    /**
     * Attempt to handle a conversation with the active AI agent.
     *
     * Returns true if the bot responded, false if it escalated or was skipped.
     */
    public function tryHandleConversation(Conversation $conv): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $agent = AiAgent::active()
            ->get()
            ->first(fn (AiAgent $a) => $a->handlesChannel($conv->channel ?? 'widget'));

        if (! $agent) {
            return false;
        }

        $lastClientMessage = $conv->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->latest()
            ->first();

        if (! $lastClientMessage) {
            return false;
        }

        // Escalate if keyword detected
        if ($agent->hasEscalationKeyword($lastClientMessage->body ?? '')) {
            Log::info('AiAgentService: escalation keyword detected', [
                'conversation_id' => $conv->id,
                'agent_id' => $agent->id,
            ]);

            $this->markEscalated($conv, $agent, 'escalation_keyword');

            return false;
        }

        // Escalate if max messages exceeded
        $botMessageCount = $conv->items()
            ->where('type', 'message')
            ->whereJsonContains('metadata->from_ai_agent', true)
            ->count();

        if ($botMessageCount >= $agent->max_messages_before_escalation) {
            Log::info('AiAgentService: max messages reached, escalating', [
                'conversation_id' => $conv->id,
                'bot_messages' => $botMessageCount,
            ]);

            $this->markEscalated($conv, $agent, 'max_messages_reached');

            return false;
        }

        $decision = $this->callOpenAiWithFunctionCalling($conv, $agent);

        if ($decision['action'] === 'escalate') {
            $this->markEscalated($conv, $agent, $decision['reason'] ?? 'ai_decided');

            return false;
        }

        if (! empty($decision['text'])) {
            $this->postBotReply($conv, $decision['text']);

            return true;
        }

        return false;
    }

    /**
     * Whether the AI agent feature is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->client->isEnabled()
            && (bool) config('helpdesk.ai.agent_enabled', env('HELPDESK_AI_AGENT_ENABLED', false));
    }

    private function callOpenAiWithFunctionCalling(Conversation $conv, AiAgent $agent): array
    {
        $messages = $this->buildMessages($conv, $agent);

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'respond_to_customer',
                    'description' => 'Send a reply to the customer.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'description' => 'The response text'],
                        ],
                        'required' => ['text'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'escalate_to_human',
                    'description' => 'Escalate the conversation to a human agent.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string', 'description' => 'Why escalating'],
                        ],
                        'required' => ['reason'],
                    ],
                ],
            ],
        ];

        try {
            $result = $this->client->chatWithTools($messages, $tools);

            if (empty($result)) {
                return ['action' => 'escalate', 'reason' => 'no_ai_response'];
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('AiAgentService: OpenAI call failed', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);

            return ['action' => 'escalate', 'reason' => 'ai_error'];
        }
    }

    private function buildMessages(Conversation $conv, AiAgent $agent): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($conv, $agent)],
        ];

        $items = $conv->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->oldest()
            ->limit(20)
            ->get();

        foreach ($items as $item) {
            $role = $item->isFromAgent() ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => strip_tags($item->body ?? '')];
        }

        return $messages;
    }

    private function buildSystemPrompt(Conversation $conv, AiAgent $agent): string
    {
        $prompt = $agent->system_prompt;
        $kbContext = $this->buildKnowledgeContext($agent);

        if ($kbContext) {
            $prompt .= "\n\n## Base de conocimiento relevante\n".$kbContext;
        }

        return $prompt;
    }

    private function buildKnowledgeContext(AiAgent $agent): string
    {
        $sources = $agent->knowledge_sources ?? [];
        $categoryIds = $sources['kb_categories'] ?? [];
        $articleIds = $sources['articles'] ?? [];

        $query = HelpCenterArticle::query()
            ->where('is_published', true)
            ->select(['title', 'content']);

        if (! empty($articleIds)) {
            $query->whereIn('id', $articleIds);
        } elseif (! empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        } else {
            return '';
        }

        return $query->limit(5)->get()
            ->map(fn ($a) => "### {$a->title}\n".strip_tags($a->content))
            ->implode("\n\n");
    }

    private function postBotReply(Conversation $conv, string $text): void
    {
        $conv->items()->create([
            'type' => 'message',
            'body' => $text,
            'html_body' => nl2br(e($text)),
            'is_internal' => false,
            'metadata' => ['from_ai_agent' => true],
        ]);

        $conv->update(['last_message_at' => now()]);

        Log::info('AiAgentService: bot replied', [
            'conversation_id' => $conv->id,
        ]);
    }

    private function markEscalated(Conversation $conv, AiAgent $agent, string $reason): void
    {
        $existing = $conv->metadata ?? [];
        $conv->update([
            'metadata' => array_merge($existing, [
                'ai_agent_escalated' => true,
                'ai_agent_escalation_reason' => $reason,
                'ai_agent_escalated_at' => now()->toIso8601String(),
            ]),
        ]);

        event(new AiAgentEscalated($conv, $agent, $reason));
    }
}
