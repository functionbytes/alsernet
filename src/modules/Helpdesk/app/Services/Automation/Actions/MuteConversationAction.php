<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class MuteConversationAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'mute_conversation';
    }

    public static function paramSchema(): array
    {
        return [];
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

        // muted_at is not in $fillable; use direct update via query builder
        Conversation::query()
            ->where('id', $conversation->id)
            ->update(['muted_at' => now()]);
    }
}
