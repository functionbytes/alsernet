<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class AssignAgentAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'assign_agent';
    }

    public static function paramSchema(): array
    {
        return [
            'agent_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'ID del agente específico'],
            'mode' => ['type' => 'string', 'enum' => ['round_robin', 'load_balanced'], 'nullable' => true, 'description' => 'Modo de asignación automática'],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;

        if (! $conversation instanceof Conversation) {
            return;
        }

        $agentId = $this->resolveAgentId($params, $conversation);

        if (! $agentId) {
            Log::warning('AssignAgentAction: could not resolve agent', ['params' => $params]);

            return;
        }

        $conversation->update([
            'assignee_id' => $agentId,
            'assigned_at' => now(),
        ]);

        event(new ConversationAssigned($conversation));
    }

    private function resolveAgentId(array $params, Conversation $conversation): ?int
    {
        if (! empty($params['agent_id'])) {
            return (int) $params['agent_id'];
        }

        $mode = $params['mode'] ?? null;
        $groupId = $conversation->group_id;

        if (! $groupId || ! $mode) {
            return null;
        }

        $group = Group::find($groupId);

        if (! $group) {
            return null;
        }

        $agent = match ($mode) {
            'round_robin', 'load_balanced' => $group->getNextAgent(),
            default => null,
        };

        return $agent?->id;
    }
}
