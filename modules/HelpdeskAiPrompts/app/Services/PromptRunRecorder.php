<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;

/**
 * Persists one AiPromptRun per agent turn, and links it later to the
 * ConversationItem the answer ended up in (the run is written before the
 * message item exists), plus the customer's 👍/👎 feedback on it.
 */
class PromptRunRecorder
{
    public function record(?string $traceId, ?string $caseKey, string $routedBy, string $action, array $usedTools, ?int $latencyMs): ?AiPromptRun
    {
        // Test runner traces ("test-*") never hit the real metrics.
        if ($traceId !== null && str_starts_with($traceId, 'test-')) {
            return null;
        }

        return AiPromptRun::query()->create([
            'trace_id' => $traceId,
            'case_key' => $caseKey,
            'routed_by' => $routedBy,
            'action' => $action,
            'used_tools' => array_values($usedTools),
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);
    }

    /**
     * If this item was posted by the AI agent (metadata.ai_agent), links the
     * latest unlinked run for the session's trace_id to it and tags the item
     * with the resolved case (ai_case), without depending on the
     * HelpdeskChatFlow provider being booted (plain query on its table).
     */
    public function linkConversationItem(ConversationItem $item): void
    {
        if (! ($item->metadata['ai_agent'] ?? false)) {
            return;
        }

        $traceId = $this->latestSessionTraceId($item->conversation_id);

        if ($traceId === null) {
            return;
        }

        $run = AiPromptRun::query()
            ->where('trace_id', $traceId)
            ->whereNull('item_id')
            ->latest('id')
            ->first();

        if ($run === null) {
            return;
        }

        $run->update(['item_id' => $item->id, 'conversation_id' => $item->conversation_id]);

        $metadata = $item->metadata ?? [];
        $metadata['ai_case'] = $run->case_key;
        $item->metadata = $metadata;
        $item->saveQuietly();
    }

    public function recordFeedback(int $itemId, int $value): void
    {
        AiPromptRun::query()
            ->where('item_id', $itemId)
            ->latest('id')
            ->limit(1)
            ->update(['feedback' => $value]);
    }

    /**
     * Protected (not private) so tests can stub the session lookup without
     * depending on the HelpdeskChatFlow migrations being loaded.
     */
    protected function latestSessionTraceId(?int $conversationId): ?string
    {
        if ($conversationId === null || ! Schema::connection('helpdesk')->hasTable('helpdesk_chat_flow_sessions')) {
            return null;
        }

        $row = DB::connection('helpdesk')->table('helpdesk_chat_flow_sessions')
            ->where('conversation_id', $conversationId)
            ->orderByRaw("status = 'active' desc")
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            return null;
        }

        $context = json_decode((string) $row->context, true) ?: [];
        $traceId = $context['_trace_id'] ?? null;

        return is_string($traceId) && $traceId !== '' ? $traceId : null;
    }
}
