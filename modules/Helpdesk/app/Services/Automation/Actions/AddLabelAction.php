<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class AddLabelAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'add_label';
    }

    public static function paramSchema(): array
    {
        return [
            'label_ids' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'required' => true,
                'description' => 'IDs de etiquetas a agregar',
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

        // Descarta IDs de etiquetas que ya no existen (regla desactualizada,
        // etiqueta borrada, etc.) — de lo contrario el pivot FK revienta con
        // QueryException en cada ejecución.
        $validIds = ConversationTag::query()->whereIn('id', array_map('intval', $labelIds))->pluck('id')->all();

        if (empty($validIds)) {
            return;
        }

        // Attach without detaching existing labels
        $conversation->conversationTags()->syncWithoutDetaching($validIds);
    }
}
