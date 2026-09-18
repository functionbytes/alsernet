<?php

namespace Modules\Helpdesk\Services\Macros;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\Automation\AutomationActionRegistry;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;

/**
 * Executes a macro's actions on a conversation, reusing the automation
 * action registry. Liquid templates inside text fields (body, note) are
 * rendered against the conversation context.
 */
class MacroExecutorService
{
    /**
     * Map macro action types → automation action types.
     */
    private const TYPE_MAP = [
        'assign_agent' => 'assign_agent',
        'assign_group' => 'assign_team',
        'add_tag' => 'add_label',
        'remove_tag' => 'remove_label',
        'change_status' => 'change_status',
        'change_priority' => 'change_priority',
        'add_note' => 'add_private_note',
        'send_reply' => 'send_message',
        'resolve_conversation' => 'change_status',
        'close_conversation' => 'change_status',
    ];

    public function __construct(
        private readonly AutomationActionRegistry $registry,
        private readonly LiquidRenderer $renderer,
    ) {}

    public function apply(Macro $macro, Conversation $conversation, ?int $userId = null): array
    {
        $context = [
            'conversation' => $conversation,
            'customer' => $conversation->customer,
            'inbox' => $conversation->inbox,
            'agent' => $conversation->assignee,
            'event_name' => 'macro.applied',
        ];

        $executed = [];
        $failed = [];
        $currentType = null;

        // Ejecución atómica: todas las mutaciones de las acciones van en una
        // transacción sobre la conexión helpdesk. Si una acción falla a mitad,
        // se revierte TODO (nada de estado parcial); los tipos desconocidos no
        // mutan nada, así que solo se reportan y no abortan el macro. Los side
        // effects encolados (webhooks/mensajes salientes) usan afterCommit()
        // en las acciones, por lo que el rollback también cancela su dispatch.
        try {
            DB::connection('helpdesk')->transaction(function () use ($macro, $conversation, $context, &$executed, &$failed, &$currentType): void {
                foreach ($macro->actions ?? [] as $action) {
                    $type = $action['type'] ?? null;
                    $params = $action['params'] ?? [];

                    $automationType = self::TYPE_MAP[$type] ?? null;
                    if (! $automationType) {
                        $failed[] = ['type' => $type, 'reason' => 'unknown action type'];

                        continue;
                    }

                    $params = $this->renderTextParams($params, $conversation);

                    if ($type === 'resolve_conversation') {
                        $params['status'] = 'resolved';
                    } elseif ($type === 'close_conversation') {
                        $params['status'] = 'closed';
                    }

                    $currentType = $type;
                    $impl = $this->registry->resolve($automationType);
                    $impl->execute($params, $context);
                    $executed[] = $type;
                }
            });
        } catch (\Throwable $e) {
            Log::error('Macro action failed', [
                'macro_id' => $macro->id,
                'action_type' => $currentType,
                'error' => $e->getMessage(),
            ]);

            // Rollback: lo marcado como ejecutado dentro de la transacción no persistió.
            $executed = [];
            $failed[] = ['type' => $currentType, 'reason' => $e->getMessage()];
        }

        // Un único UPDATE en vez de increment + update (dos escrituras).
        $macro->update([
            'usage_count' => DB::raw('usage_count + 1'),
            'last_used_at' => now(),
        ]);

        return ['executed' => $executed, 'failed' => $failed];
    }

    /**
     * Render Liquid templates in text-bearing parameters (body, note, message).
     */
    private function renderTextParams(array $params, Conversation $conversation): array
    {
        foreach (['body', 'note', 'message', 'text'] as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                $params[$key] = $this->renderer->renderForConversation($params[$key], $conversation);
            }
        }

        return $params;
    }
}
