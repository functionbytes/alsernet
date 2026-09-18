<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\DB;
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
     * Silenciar es por-agente (helpdesk_user_conversation_meta.muted_until,
     * igual que ConversationsController::toggleMute) — "muted_at" en
     * helpdesk_conversations no existe. Una automatización no tiene un
     * "usuario actual", así que silencia para el agente asignado (quien
     * recibiría las notificaciones); si no hay nadie asignado, no hay nada
     * que silenciar.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;

        if (! $conversation instanceof Conversation || ! $conversation->assignee_id) {
            return;
        }

        $userId = $conversation->assignee_id;
        $until = now()->addDays(7);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        if ($meta) {
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->where('id', $meta->id)
                ->update(['muted_until' => $until, 'updated_at' => now()]);

            return;
        }

        DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->insert([
                'user_id' => $userId,
                'conversation_id' => $conversation->id,
                'pinned_at' => null,
                'muted_until' => $until,
                'blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
