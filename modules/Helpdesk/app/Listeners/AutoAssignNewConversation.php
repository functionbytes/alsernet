<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Jobs\ReattemptAutoAssignJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\Helpdesk\Services\RoutingRuleService;
use Modules\Helpdesk\Services\SkillsRoutingService;

/**
 * Auto-assign / route a freshly created conversation.
 *
 * Gated by config('helpdesk.auto_assignment.enabled') (default false → inert).
 * Idempotent: skips conversations that already have an assignee or group.
 * Each stage is wrapped in try/catch and never re-throws so a routing failure
 * cannot break the inbound pipeline that dispatched the event.
 */
class AutoAssignNewConversation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly RoutingRuleService $routingRuleService,
        private readonly SkillsRoutingService $skillsRoutingService,
        private readonly AutoAssignmentService $autoAssignmentService,
    ) {}

    public function handle(ConversationCreated $event): void
    {
        if (! $this->autoAssignmentService->isEnabled()) {
            return;
        }

        $conversation = $event->conversation;

        if ($conversation->assignee_id || $conversation->group_id) {
            return;
        }

        $firstItem = $conversation->items->first();

        if ($firstItem === null) {
            return;
        }

        try {
            if ($this->routingRuleService->matchAndAssign($conversation, $firstItem)) {
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('AutoAssignNewConversation: routing rule stage failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            // Detect skills first so the "skills" strategy has data to route on.
            $this->skillsRoutingService->detectAndAttachSkills($conversation, (string) $firstItem->body);

            $userId = $this->autoAssignmentService->resolveAgent($conversation);

            if ($userId) {
                $conversation->assignTo($userId);

                return;
            }

            // No agent available now: retry after the configured delay, else fallback.
            $this->handleNoAgent($conversation);
        } catch (\Throwable $e) {
            Log::warning('AutoAssignNewConversation: strategy stage failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleNoAgent(Conversation $conversation): void
    {
        $config = $this->autoAssignmentService->config();

        // "Manual" deja la conversación sin asignar: ni reintento ni fallback.
        if ($config['strategy'] === 'manual') {
            return;
        }

        if ($config['retry'] !== 'off') {
            ReattemptAutoAssignJob::dispatch($conversation->id)->delay(now()->addMinutes((int) $config['retry']));

            return;
        }

        $this->autoAssignmentService->applyFallback($conversation);
    }

    public function failed(ConversationCreated $event, \Throwable $exception): void
    {
        Log::error('AutoAssignNewConversation failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
