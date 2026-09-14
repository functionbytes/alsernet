<?php

namespace Modules\Helpdesk\Services\Macros;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\Automation\AutomationActionRegistry;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;
use Throwable;

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
            DB::connection('helpdesk')->transaction(function () use ($macro, $conversation, $userId, $context, &$executed, &$failed, &$currentType): void {
                foreach ($macro->actions ?? [] as $action) {
                    $type = $action['type'] ?? null;

                    // El formulario real de macros (StoreMacroRequest/
                    // UpdateMacroRequest, MacroFactory, HelpdeskDemoDataSeeder)
                    // guarda un unico `value` plano por accion — NO el `params`
                    // estructurado que usa AutomationEngine para las reglas de
                    // automatizacion (mismo registro de acciones, forma de
                    // datos distinta). Si algun dia una fila SI trae `params`
                    // explicito se respeta tal cual; si no, se deriva del
                    // `value` guardado. Sin esto toda macro creada desde la UI
                    // se "aplicaba" con params=[] y cada accion no hacia nada.
                    $params = array_key_exists('params', $action)
                        ? (array) $action['params']
                        : $this->paramsFromValue((string) $type, $action['value'] ?? null, $userId);

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
        } catch (Throwable $e) {
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
     * Traduce el `value` plano que guarda una macro a la forma de `params`
     * que espera la accion correspondiente del registro de automatizacion.
     *
     * @return array<string, mixed>
     */
    private function paramsFromValue(string $type, mixed $value, ?int $userId): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return match ($type) {
            // 'me' es el unico valor que el formulario de macros NO puede
            // producir (el select solo ofrece agentes reales por id): es el
            // sentinel de HelpdeskDemoDataSeeder para "quien aplica el
            // macro", el unico sentido posible en una macro personal de
            // autoasignacion.
            'assign_agent' => $value === 'me'
                ? ['agent_id' => $userId]
                : (is_numeric($value) ? ['agent_id' => (int) $value] : []),
            'assign_group' => is_numeric($value) ? ['team_id' => (int) $value] : [],
            'add_tag', 'remove_tag' => is_numeric($value) ? ['label_ids' => [(int) $value]] : [],
            'change_status' => ['status' => (string) $value],
            'change_priority' => ['priority' => (string) $value],
            'add_note' => ['note' => (string) $value],
            'send_reply' => ['body' => (string) $value],
            default => [],
        };
    }

    /**
     * Render Liquid templates in text-bearing parameters (body, note, message).
     *
     * `LiquidRenderer` no se inyecta por constructor: depende del paquete
     * `keepsuit/liquid`, que no esta instalado (no aparece en
     * composer.lock/vendor) — su constructor monta el Environment de forma
     * inmediata, asi que un `LiquidRenderer $renderer` en el constructor de
     * este servicio tumbaba con 500 la aplicacion de CUALQUIER macro antes
     * de ejecutar una sola accion (probado en vivo). Se resuelve aqui dentro
     * y con try/catch, mismo patron defensivo que ya usan SendMessageAction
     * y AddPrivateNoteAction para el mismo motivo: si no esta disponible,
     * se deja el texto sin renderizar en vez de fallar.
     */
    private function renderTextParams(array $params, Conversation $conversation): array
    {
        foreach (['body', 'note', 'message', 'text'] as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                try {
                    $params[$key] = app(LiquidRenderer::class)->renderForConversation($params[$key], $conversation);
                } catch (Throwable $e) {
                    Log::warning('MacroExecutorService: LiquidRenderer failed, using raw text', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $params;
    }
}
