<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Closure;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;

/**
 * Procedures: flows that are only started by being called from another flow.
 *
 * `call_flow` pushes a frame on the session's return stack (`_call_stack`),
 * points the session at the called flow and returns its start node. `return`
 * (or the natural end of the called flow, resolved by the engine through
 * {@see resumeCaller()}) pops the frame and continues at the caller's node.
 *
 * The called flow shares the session context. `input` values are written
 * before entering and, on return, restored to what the caller had unless they
 * are listed in `output`.
 *
 * call_flow data: flow_id, input {var: template}, output [var...],
 * on_missing = continue|handoff.
 */
class FlowCallNodeHandler implements NodeHandler
{
    public const TYPES = ['call_flow', 'return'];

    public const STACK_KEY = '_call_stack';

    public const MAX_DEPTH = 3;

    /** @var Closure(int): ?ChatFlow|null */
    private readonly ?Closure $flowLoader;

    /**
     * @param  (Closure(int): ?ChatFlow)|null  $flowLoader  Overrides how flows are loaded (tests).
     */
    public function __construct(?Closure $flowLoader = null)
    {
        $this->flowLoader = $flowLoader;
    }

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        if ($node['type'] === 'return') {
            return $this->resumeCaller($session);
        }

        return $this->executeCall($node, $session);
    }

    /**
     * Enters a procedure and returns the id of its start node.
     *
     * @param  array<string, mixed>  $input  Variables (already rendered) given to the procedure.
     * @param  array<int, string>  $output  Variables that stay set after the return.
     *
     * @throws FlowCallRefused When the procedure can't be entered (not callable, cycle, too deep).
     */
    public function enter(ChatFlowSession $session, ChatFlow $procedure, ?string $returnNodeId, array $input = [], array $output = []): string
    {
        $this->assertCallable($session, $procedure);

        $startNode = $procedure->getStartNode();

        if ($startNode === null) {
            throw new FlowCallRefused("El procedimiento {$procedure->id} no tiene nodo de inicio.");
        }

        $context = $session->context ?? [];
        $saved = [];

        foreach ($input as $name => $value) {
            $saved[$name] = array_key_exists($name, $context)
                ? ['had' => true, 'value' => $context[$name]]
                : ['had' => false];
        }

        $stack = $this->stack($session);
        $stack[] = [
            'flow_id' => (int) $session->chat_flow_id,
            'return_node_id' => $returnNodeId,
            'saved_vars' => $saved,
            'output' => array_values($output),
        ];

        $session->setContextValues([...$input, self::STACK_KEY => $stack]);
        $this->switchFlow($session, $procedure);

        return $startNode['id'];
    }

    /**
     * Pops the return stack and returns the node the caller continues at, or
     * null when there is no caller (or the caller has nothing left to run).
     */
    public function resumeCaller(ChatFlowSession $session): ?string
    {
        $stack = $this->stack($session);

        while ($stack !== []) {
            $frame = array_pop($stack);

            $this->restoreInputs($session, $frame, $stack);
            $this->switchFlow($session, $this->load((int) $frame['flow_id']));

            if (! empty($frame['return_node_id'])) {
                return $frame['return_node_id'];
            }
        }

        return null;
    }

    public function hasCaller(ChatFlowSession $session): bool
    {
        return $this->stack($session) !== [];
    }

    /**
     * When a session ends inside a procedure, attribute it (analytics, cache
     * invalidation) to the flow that started it, not to the procedure.
     */
    public function restoreRootFlow(ChatFlowSession $session): void
    {
        $stack = $this->stack($session);

        if ($stack === [] || (int) $session->chat_flow_id === (int) $stack[0]['flow_id']) {
            return;
        }

        $root = $this->load((int) $stack[0]['flow_id']);

        if ($root !== null) {
            $this->switchFlow($session, $root);
        }
    }

    private function executeCall(array $node, ChatFlowSession $session): ?string
    {
        $data = $node['data'] ?? [];
        $continueAt = $this->getFirstChildId($node, $session);

        try {
            $procedure = $this->load((int) ($data['flow_id'] ?? 0));

            if ($procedure === null) {
                throw new FlowCallRefused('El procedimiento llamado no existe.');
            }

            return $this->enter(
                $session,
                $procedure,
                $continueAt,
                $this->renderInput($data['input'] ?? [], $session),
                (array) ($data['output'] ?? []),
            );
        } catch (FlowCallRefused $e) {
            Log::warning('ChatFlow call_flow refused', [
                'session_id' => $session->id,
                'node_id' => $node['id'],
                'reason' => $e->getMessage(),
            ]);

            return $this->onMissing($data, $session, $continueAt);
        }
    }

    private function onMissing(array $data, ChatFlowSession $session, ?string $continueAt): ?string
    {
        if (($data['on_missing'] ?? 'continue') !== 'handoff') {
            return $continueAt;
        }

        $session->conversation?->releaseFromBot();
        $session->update(['status' => 'transferred', 'ended_at' => now()]);

        return null;
    }

    private function assertCallable(ChatFlowSession $session, ChatFlow $procedure): void
    {
        if ($procedure->status !== 'active' || $procedure->trigger_type !== 'procedure') {
            throw new FlowCallRefused("El flujo {$procedure->id} no es un procedimiento activo.");
        }

        $stack = $this->stack($session);

        if (count($stack) >= self::MAX_DEPTH) {
            throw new FlowCallRefused('Profundidad máxima de llamadas superada.');
        }

        $activeFlowIds = [(int) $session->chat_flow_id, ...array_map(fn (array $frame): int => (int) $frame['flow_id'], $stack)];

        if (in_array((int) $procedure->id, $activeFlowIds, true)) {
            throw new FlowCallRefused("Llamada cíclica al flujo {$procedure->id}.");
        }
    }

    /**
     * @param  array<string, mixed>  $frame
     * @param  array<int, array<string, mixed>>  $remainingStack
     */
    private function restoreInputs(ChatFlowSession $session, array $frame, array $remainingStack): void
    {
        $kept = $frame['output'] ?? [];
        $values = [self::STACK_KEY => $remainingStack];

        foreach ($frame['saved_vars'] ?? [] as $name => $previous) {
            if (in_array($name, $kept, true)) {
                continue;
            }

            $values[$name] = ($previous['had'] ?? false) ? $previous['value'] : null;
        }

        $session->setContextValues($values);
    }

    /**
     * @param  array<string, mixed>  $templates
     * @return array<string, mixed>
     */
    private function renderInput(array $templates, ChatFlowSession $session): array
    {
        $context = $session->context ?? [];
        $rendered = [];

        foreach ($templates as $name => $template) {
            $rendered[$name] = is_string($template) ? ContextPath::interpolate($template, $context) : $template;
        }

        return $rendered;
    }

    private function switchFlow(ChatFlowSession $session, ?ChatFlow $flow): void
    {
        if ($flow === null) {
            return;
        }

        $session->update(['chat_flow_id' => $flow->id]);
        $session->setRelation('chatFlow', $flow);
    }

    public function load(int $flowId): ?ChatFlow
    {
        if ($this->flowLoader !== null) {
            return ($this->flowLoader)($flowId);
        }

        return ChatFlow::query()->find($flowId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stack(ChatFlowSession $session): array
    {
        $stack = $session->getContextValue(self::STACK_KEY, []);

        return is_array($stack) ? array_values($stack) : [];
    }

    private function getFirstChildId(array $node, ChatFlowSession $session): ?string
    {
        return $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;
    }
}
