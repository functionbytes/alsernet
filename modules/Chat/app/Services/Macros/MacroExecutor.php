<?php

namespace Modules\Chat\Services\Macros;

use App\Models\User;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Macro;
use Modules\Chat\Services\Conversations\ConversationLabelService;

class MacroExecutor
{
    public function __construct(
        protected MacroConditionEvaluator $conditionEvaluator,
        protected ConversationLabelService $labelService
    ) {}

    /**
     * Execute a macro on a conversation
     */
    public function execute(Macro $macro, Conversation $conversation): array
    {
        // Check if macro is enabled
        if (! $macro->enabled) {
            return [[
                'success' => false,
                'error' => 'Macro is disabled',
            ]];
        }

        // Evaluate conditions
        if (! $this->conditionEvaluator->evaluate($macro, $conversation)) {
            return [[
                'success' => false,
                'error' => 'Macro conditions not met',
            ]];
        }

        $results = [];
        $startTime = now();

        foreach ($macro->actions as $action) {
            $result = $this->executeAction($action, $conversation);
            $results[] = $result;
        }

        // Log macro execution
        activity()
            ->performedOn($conversation)
            ->causedBy(auth()->user())
            ->withProperties([
                'macro_id' => $macro->id,
                'macro_name' => $macro->name,
                'actions_count' => count($macro->actions),
                'executed_at' => $startTime->toDateTimeString(),
                'results' => $results,
            ])
            ->log('macro_executed');

        return $results;
    }

    /**
     * Execute a single action
     */
    protected function executeAction(array $action, Conversation $conversation): array
    {
        // Handle old format: { action_name, action_params } → { action, value }
        $actionType = $action['action'] ?? $action['action_name'] ?? null;
        if (! isset($action['value']) && isset($action['action_params'])) {
            $params = $action['action_params'];
            // Extract value from action_params
            if (is_array($params)) {
                $paramValue = reset($params); // Get first value from the dict
                // If the value is an array, get the first element (e.g., for labels: ["Bug"] → "Bug")
                $value = is_array($paramValue) ? reset($paramValue) : $paramValue;
            } else {
                $value = $params;
            }
        } else {
            $value = $action['value'] ?? null;
        }

        try {
            $executed = match ($actionType) {
                'assign_agent' => $this->assignAgent($conversation, $value),
                'assign_team' => $this->assignTeam($conversation, $value),
                'add_label' => $this->addLabel($conversation, $value),
                'remove_label' => $this->removeLabel($conversation, $value),
                'change_status' => $this->changeStatus($conversation, $value),
                'change_priority' => $this->changePriority($conversation, $value),
                'send_message' => $this->sendMessage($conversation, $value),
                'add_private_note' => $this->addPrivateNote($conversation, $value),
                'mute_conversation' => $this->muteConversation($conversation),
                'unmute_conversation' => $this->unmuteConversation($conversation),
                'resolve_conversation' => $this->resolveConversation($conversation),
                'reopen_conversation' => $this->reopenConversation($conversation),
                default => ['success' => false, 'error' => 'Unknown action type'],
            };

            return [
                'action' => $actionType,
                'success' => true,
                'result' => $executed,
            ];
        } catch (\Exception $e) {
            return [
                'action' => $actionType,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assign agent to conversation
     */
    protected function assignAgent(Conversation $conversation, int $userId): bool
    {
        $conversation->update(['assignee_id' => $userId]);

        return true;
    }

    /**
     * Assign team to conversation
     */
    protected function assignTeam(Conversation $conversation, int $teamId): bool
    {
        $conversation->update(['team_id' => $teamId]);

        return true;
    }

    /**
     * Add label to conversation
     */
    protected function addLabel(Conversation $conversation, string $labelTitle): bool
    {
        $this->labelService->addLabel($conversation, $labelTitle);

        return true;
    }

    /**
     * Remove label from conversation
     */
    protected function removeLabel(Conversation $conversation, string $labelTitle): bool
    {
        $this->labelService->removeLabel($conversation, $labelTitle);

        return true;
    }

    /**
     * Change conversation status
     */
    protected function changeStatus(Conversation $conversation, string $status): bool
    {
        $conversation->update(['status' => $status]);

        return true;
    }

    /**
     * Change conversation priority
     */
    protected function changePriority(Conversation $conversation, string $priority): bool
    {
        $conversation->update(['priority' => $priority]);

        return true;
    }

    /**
     * Send message in conversation
     */
    protected function sendMessage(Conversation $conversation, string $content): bool
    {
        $conversation->messages()->create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'sender_id' => auth()->id(),
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content' => $content,
            'content_type' => 'text',
            'private' => false,
        ]);

        return true;
    }

    /**
     * Add private note
     */
    protected function addPrivateNote(Conversation $conversation, string $content): bool
    {
        $conversation->messages()->create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'sender_id' => auth()->id(),
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content' => $content,
            'content_type' => 'text',
            'private' => true,
        ]);

        return true;
    }

    /**
     * Mute conversation
     */
    protected function muteConversation(Conversation $conversation): bool
    {
        $conversation->update([
            'muted' => true,
            'muted_at' => now(),
            'muted_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Unmute conversation
     */
    protected function unmuteConversation(Conversation $conversation): bool
    {
        $conversation->update([
            'muted' => false,
            'muted_at' => null,
            'muted_by' => null,
        ]);

        return true;
    }

    /**
     * Resolve conversation
     */
    protected function resolveConversation(Conversation $conversation): bool
    {
        $conversation->markAsResolved();

        return true;
    }

    /**
     * Reopen conversation
     */
    protected function reopenConversation(Conversation $conversation): bool
    {
        $conversation->markAsOpen();

        return true;
    }
}
