<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationCreated;
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
    ) {}

    public function handle(ConversationCreated $event): void
    {
        if (! config('helpdesk.auto_assignment.enabled')) {
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
            $this->skillsRoutingService->detectAndAttachSkills($conversation, (string) $firstItem->body);

            $userId = $this->skillsRoutingService->routeBySkills($conversation);

            if ($userId) {
                $conversation->assignTo($userId);
            }
        } catch (\Throwable $e) {
            Log::warning('AutoAssignNewConversation: skills routing stage failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ConversationCreated $event, \Throwable $exception): void
    {
        Log::error('AutoAssignNewConversation failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
