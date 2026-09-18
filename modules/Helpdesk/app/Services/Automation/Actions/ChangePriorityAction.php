<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class ChangePriorityAction implements AutomationAction
{
    private const VALID_PRIORITIES = ['low', 'medium', 'normal', 'high', 'urgent'];

    public static function actionType(): string
    {
        return 'change_priority';
    }

    public static function paramSchema(): array
    {
        return [
            'priority' => [
                'type' => 'string',
                'required' => true,
                'enum' => self::VALID_PRIORITIES,
                'description' => 'Nueva prioridad de la conversación',
            ],
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

        $priority = $params['priority'] ?? null;

        if (! $priority || ! in_array($priority, self::VALID_PRIORITIES, true)) {
            return;
        }

        $conversation->update(['priority' => $priority]);
    }
}
