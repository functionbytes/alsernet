<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class RemoveLabelAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'remove_label';
    }

    public static function paramSchema(): array
    {
        return [
            'label_ids' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'required' => true,
                'description' => 'IDs de etiquetas a eliminar',
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

        $labelIds = array_filter((array) ($params['label_ids'] ?? []), 'is_numeric');

        if (empty($labelIds)) {
            return;
        }

        $conversation->conversationTags()->detach(array_map('intval', $labelIds));
    }
}
