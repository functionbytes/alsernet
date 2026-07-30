<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class ChatFlowTriggerResolver
{
    public function __construct(
        private readonly ?ChatFlowAiResponder $aiResponder = null,
    ) {}

    /**
     * Finds the active ChatFlow that should fire for a conversation and trigger type.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(Conversation $conversation, string $triggerType, array $context = []): ?ChatFlow
    {
        $query = ChatFlow::query()
            ->active()
            ->where('trigger_type', $triggerType)
            ->forInbox($conversation->inbox_id)
            ->orderByDesc('priority')
            ->orderBy('id');

        $message = isset($context['message']) ? trim((string) $context['message']) : '';

        if ($triggerType === 'keyword' && isset($context['message'])) {
            $candidates = $query->get();

            // Exact keyword (substring) match always wins and keeps its priority order.
            $matched = $candidates->first(fn (ChatFlow $flow) => $this->matchesKeyword($flow, $message));

            if ($matched !== null) {
                return $matched;
            }

            // ADDITIVE: no keyword matched → semantic fallback for opt-in flows only.
            // resolveByIntent() is a no-op (null) when the message is empty, so the
            // original "no match → null" behaviour is preserved.
            return $this->resolveByIntent($candidates, $message);
        }

        // ADDITIVE: explicit intent/nlu trigger types route semantically when a
        // message is present. No existing flow uses these types, so current
        // behaviour for the four canonical trigger types is unchanged.
        if (in_array($triggerType, ['intent', 'nlu'], true) && $message !== '') {
            return $this->resolveByIntent($query->get(), $message, requireOptIn: false)
                ?? $query->first();
        }

        return $query->first();
    }

    private function matchesKeyword(ChatFlow $flow, string $message): bool
    {
        $keywords = $flow->trigger_conditions['keywords'] ?? [];
        $message = strtolower(trim($message));

        foreach ($keywords as $keyword) {
            if (str_contains($message, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Picks the best flow for a free-text message using the existing intent
     * classifier. Conservative by design: returns null (no flow) whenever the
     * classifier is unavailable, no flow opts in, or nothing matches clearly.
     *
     * @param  Collection<int, ChatFlow>  $candidates
     */
    private function resolveByIntent(Collection $candidates, string $message, bool $requireOptIn = true): ?ChatFlow
    {
        if ($this->aiResponder === null || $message === '') {
            return null;
        }

        $eligible = $requireOptIn
            ? $candidates->filter(fn (ChatFlow $flow) => $this->usesNlu($flow))->values()
            : $candidates->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        $options = $eligible->map(fn (ChatFlow $flow) => $this->intentLabel($flow))->all();

        $index = $this->classifyCached($message, $options);

        if ($index === null) {
            return null;
        }

        return $eligible->get($index - 1);
    }

    /**
     * Cache the classification to bound LLM cost: identical text against the same
     * ordered candidate set resolves once per TTL window.
     *
     * @param  array<int, string>  $options
     */
    private function classifyCached(string $message, array $options): ?int
    {
        $ttl = (int) config('helpdeskchatflow.ai.intent_cache_ttl', 300);
        $key = 'chatflow:intent:'.md5(strtolower($message).'|'.implode('|', $options));

        $resolve = fn (): int => $this->aiResponder->classifyIntent($message, $options) ?? 0;

        $index = $ttl > 0
            ? (int) Cache::remember($key, $ttl, $resolve)
            : $resolve();

        return $index >= 1 ? $index : null;
    }

    private function usesNlu(ChatFlow $flow): bool
    {
        $conditions = $flow->trigger_conditions ?? [];

        return (bool) ($conditions['use_nlu'] ?? false)
            || filled($conditions['intent'] ?? null);
    }

    /**
     * Human-readable intent the classifier matches the customer message against.
     * Prefers an explicit intent description, then the flow name, then keywords.
     */
    private function intentLabel(ChatFlow $flow): string
    {
        $conditions = $flow->trigger_conditions ?? [];

        $intent = trim((string) ($conditions['intent'] ?? ''));
        if ($intent !== '') {
            return $intent;
        }

        $name = trim((string) ($flow->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $keywords = $conditions['keywords'] ?? [];

        return is_array($keywords) ? implode(', ', $keywords) : '';
    }
}
