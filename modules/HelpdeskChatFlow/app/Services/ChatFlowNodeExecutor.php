<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandlerRegistry;

/**
 * Runs one node: routing-only nodes are resolved here; every other type is
 * delegated to its NodeHandler (see NodeHandlerRegistry). Each customer-visible
 * node run is logged as a ChatFlowExecution row for analytics/replay.
 */
class ChatFlowNodeExecutor
{
    public function __construct(
        private readonly NodeHandlerRegistry $handlers,
    ) {}

    /**
     * Node types resolved by the executor itself (graph navigation only).
     */
    public const ROUTING_TYPES = ['start', 'branches', 'branchItem', 'delay', 'go_to_step'];

    /**
     * Routing-only nodes that produce no customer-visible side effect: logging an
     * execution row for them is pure write overhead with no analytics value.
     */
    private const SKIP_LOGGING_TYPES = ['start', 'branchItem', 'delay', 'go_to_step'];

    /**
     * Executes a node and returns the next node ID, or null if execution should pause/end.
     */
    public function execute(array $node, ChatFlowSession $session): ?string
    {
        if (in_array($node['type'], self::SKIP_LOGGING_TYPES, true)) {
            return $this->executeNode($node, $session);
        }

        // Snapshot the pre-execution context so the logged row reflects the input
        // state even though the node may mutate context while running.
        $inputContext = $session->context;
        $startedAt = microtime(true);

        try {
            $nextNodeId = $this->executeNode($node, $session);
        } catch (\Throwable $e) {
            $this->logExecution($node, $session, 'failed', $startedAt, $inputContext, ['error_message' => $e->getMessage()]);

            throw $e;
        }

        // Single write (no pending → success UPDATE) keeps the hot path to one INSERT per node.
        $this->logExecution($node, $session, 'success', $startedAt, $inputContext, ['output' => ['next_node_id' => $nextNodeId]]);

        return $nextNodeId;
    }

    /**
     * @param  array<string,mixed>|null  $inputContext
     * @param  array<string,mixed>  $extra
     */
    private function logExecution(array $node, ChatFlowSession $session, string $status, float $startedAt, ?array $inputContext, array $extra = []): void
    {
        ChatFlowExecution::create(array_merge([
            'session_id' => $session->id,
            'node_id' => $node['id'],
            'node_type' => $node['type'],
            'input' => ['context' => $this->pruneContextSnapshot($inputContext)],
            'status' => $status,
            'executed_at' => now(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ], $extra));
    }

    /**
     * Bound the per-node context snapshot stored in the execution audit row.
     * Context grows with every node (AI answers, HTTP bodies, order payloads),
     * so logging the full blob on each node stores ever-larger copies — quadratic
     * audit-table growth for no analytics value. Long string values are truncated
     * so the debug trail keeps the keys/shape without the bloat.
     *
     * @param  array<string,mixed>|null  $context
     * @return array<string,mixed>|null
     */
    private function pruneContextSnapshot(?array $context): ?array
    {
        if ($context === null) {
            return null;
        }

        $maxLength = 500;

        foreach ($context as $key => $value) {
            if (is_string($value) && mb_strlen($value) > $maxLength) {
                $context[$key] = mb_substr($value, 0, $maxLength).'… [truncated]';
            }
        }

        return $context;
    }

    private function executeNode(array $node, ChatFlowSession $session): ?string
    {
        $handler = $this->handlers->for($node['type']);

        if ($handler !== null) {
            return $handler->handle($node, $session, $session->conversation);
        }

        return match ($node['type']) {
            'start', 'branchItem', 'delay' => $this->getFirstChildId($node, $session),
            'go_to_step' => $node['data']['target_node_id'] ?? null,
            default => null, // 'branches' is routed by the engine; unknown types stop the run
        };
    }

    private function getFirstChildId(array $node, ChatFlowSession $session): ?string
    {
        return $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;
    }
}
