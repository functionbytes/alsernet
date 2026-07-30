<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class AssignTeamAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'assign_team';
    }

    public static function paramSchema(): array
    {
        return [
            'team_id' => ['type' => 'integer', 'required' => true, 'description' => 'ID del grupo/equipo'],
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

        $teamId = (int) ($params['team_id'] ?? 0);

        if (! $teamId || ! Group::where('id', $teamId)->exists()) {
            return;
        }

        $conversation->update(['group_id' => $teamId]);
    }
}
