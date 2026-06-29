<?php

namespace Modules\Campaign\Domain\Automation;

use Illuminate\Support\Str;
use Modules\Campaign\Domain\Automation\Enum\Branch;
use Modules\Campaign\Domain\Automation\Enum\NodeType;

/**
 * Pure mutation operations on a Flow. Each method returns a NEW Flow
 * (Flow constructor re-validates invariants), throwing FlowException
 * on violations. Never mutates the input.
 */
final class FlowMutator
{
    /**
     * Insert a new node as a child of $parentId.
     * - Linear parent: replaces parent's existing outbound edge (the new node sits between parent and old child).
     * - Condition parent: $branch must be supplied; replaces that branch's edge.
     * - Leaf parent: just creates a new edge.
     */
    public static function insertAfter(Flow $flow, string $parentId, ?Branch $branch, NodeType $type, array $data): array
    {
        $parent = $flow->node($parentId);

        if ($parent->type === NodeType::Condition && $branch === null) {
            throw new FlowException("Inserting after condition {$parentId} requires a branch (yes|no)");
        }
        if ($parent->type !== NodeType::Condition && $branch !== null) {
            throw new FlowException("Branch only valid on condition parents; {$parentId} is {$parent->type->value}");
        }
        if ($type === NodeType::Trigger) {
            throw new FlowException('Cannot insert a second trigger');
        }

        $newNode = new Node(self::genNodeId(), $type, $data);
        $nodes = [...array_values($flow->nodes), $newNode];

        // Find existing outbound edge from parent matching branch (if any)
        $matching = null;
        foreach ($flow->outgoing($parentId) as $e) {
            if ($e->branch?->value === $branch?->value) {
                $matching = $e;
                break;
            }
        }

        // If newNode is a condition, the continuation edge gets the YES branch
        // (NO is left empty for user to fill). Otherwise null.
        $continuationBranch = $type === NodeType::Condition ? Branch::Yes : null;

        $edges = array_values($flow->edges);
        if ($matching !== null) {
            // Re-target: parent → newNode (preserves matching's branch),
            // then newNode → oldTarget (with continuation branch).
            $oldTarget = $matching->target;
            $edges = array_map(
                fn (Edge $e) => $e->id === $matching->id ? $e->withTarget($newNode->id) : $e,
                $edges
            );
            $edges[] = new Edge(self::genEdgeId(), $newNode->id, $oldTarget, $continuationBranch);
        } else {
            // Leaf parent: just connect
            $edges[] = new Edge(self::genEdgeId(), $parentId, $newNode->id, $branch);
        }

        return [new Flow($nodes, $edges), $newNode];
    }

    /**
     * Insert a new node BEFORE $nodeId — the new node becomes $nodeId's parent.
     * Trigger cannot have its parent replaced.
     */
    public static function insertBefore(Flow $flow, string $nodeId, NodeType $type, array $data): array
    {
        if ($nodeId === Flow::TRIGGER_ID) {
            throw new FlowException('Cannot insert a node above the trigger');
        }
        if ($type === NodeType::Trigger) {
            throw new FlowException('Cannot insert a second trigger');
        }

        $existing = $flow->node($nodeId);
        $incoming = $flow->incoming($nodeId);

        $newNode = new Node(self::genNodeId(), $type, $data);
        $nodes = [...array_values($flow->nodes), $newNode];
        $edges = array_values($flow->edges);

        $continuationBranch = $type === NodeType::Condition ? Branch::Yes : null;

        if ($incoming !== null) {
            // Re-target incoming.target → newNode (preserve branch label)
            $edges = array_map(
                fn (Edge $e) => $e->id === $incoming->id ? $e->withTarget($newNode->id) : $e,
                $edges
            );
            $edges[] = new Edge(self::genEdgeId(), $newNode->id, $existing->id, $continuationBranch);
        } else {
            // No parent yet — just connect newNode → existing
            $edges[] = new Edge(self::genEdgeId(), $newNode->id, $existing->id, $continuationBranch);
        }

        return [new Flow($nodes, $edges), $newNode];
    }

    /**
     * Insert a new node ON an edge (split edge into edge → new → edge).
     */
    public static function insertOnEdge(Flow $flow, string $edgeId, NodeType $type, array $data): array
    {
        if ($type === NodeType::Trigger) {
            throw new FlowException('Cannot insert a second trigger');
        }
        $edge = $flow->edge($edgeId);

        $newNode = new Node(self::genNodeId(), $type, $data);
        $nodes = [...array_values($flow->nodes), $newNode];

        // Re-target edge to newNode, then add newNode → oldTarget.
        // If newNode is a condition, the continuation gets the YES branch (NO is empty for user to fill).
        $oldTarget = $edge->target;
        $edges = array_map(
            fn (Edge $e) => $e->id === $edgeId ? $e->withTarget($newNode->id) : $e,
            array_values($flow->edges)
        );
        $continuationBranch = $type === NodeType::Condition ? Branch::Yes : null;
        $edges[] = new Edge(self::genEdgeId(), $newNode->id, $oldTarget, $continuationBranch);

        return [new Flow($nodes, $edges), $newNode];
    }

    /**
     * Replace a node's data entirely (preserves id and type).
     * REPLACE semantics is the consistent contract — clients send all relevant
     * fields per the type's schema; the server overwrites without merging.
     * This avoids stale-field accumulation when type/kind sub-forms change.
     */
    public static function replaceNode(Flow $flow, string $nodeId, array $data): array
    {
        $existing = $flow->node($nodeId);
        if ($existing->type === NodeType::Trigger) {
            throw new FlowException('Use replaceTrigger for trigger node — its endpoint enforces TriggerKey');
        }
        $patched = new Node($existing->id, $existing->type, $data);
        $nodes = array_map(
            fn (Node $n) => $n->id === $nodeId ? $patched : $n,
            array_values($flow->nodes)
        );

        return [new Flow($nodes, array_values($flow->edges)), $patched];
    }

    /**
     * Replace a node's TYPE + data, preserving id and edges.
     * Used by the unified Wait sidebar (mode toggle) to swap wait <-> wait_until
     * without losing the node id, position, or children. Refuses Trigger as
     * either source or target (Trigger has its own endpoint), and refuses
     * Condition (yes/no output count differs from linear types — caller would
     * need to re-wire branches; out of scope for this primitive).
     */
    public static function replaceNodeWithType(Flow $flow, string $nodeId, NodeType $newType, array $data): array
    {
        $existing = $flow->node($nodeId);
        if ($existing->type === NodeType::Trigger) {
            throw new FlowException('Cannot change type of the trigger node');
        }
        if ($newType === NodeType::Trigger) {
            throw new FlowException('Cannot turn a node into the trigger');
        }
        if ($existing->type === NodeType::Condition || $newType === NodeType::Condition) {
            throw new FlowException('Type swap to/from condition is not supported (branch outputs differ)');
        }

        $patched = new Node($existing->id, $newType, $data);
        $nodes = array_map(
            fn (Node $n) => $n->id === $nodeId ? $patched : $n,
            array_values($flow->nodes)
        );

        return [new Flow($nodes, array_values($flow->edges)), $patched];
    }

    /**
     * Replace trigger's data entirely (preserves id "trigger" and type).
     */
    public static function replaceTrigger(Flow $flow, array $data): array
    {
        $trigger = $flow->trigger();
        $patched = new Node($trigger->id, NodeType::Trigger, $data);
        $nodes = array_map(
            fn (Node $n) => $n->id === Flow::TRIGGER_ID ? $patched : $n,
            array_values($flow->nodes)
        );

        return [new Flow($nodes, array_values($flow->edges)), $patched];
    }

    /**
     * Delete node + redirect parent edge to the (single) child.
     * Refuses on trigger or condition.
     */
    public static function deleteShift(Flow $flow, string $nodeId): Flow
    {
        if ($nodeId === Flow::TRIGGER_ID) {
            throw new FlowException('Cannot delete the trigger');
        }
        $node = $flow->node($nodeId);
        if ($node->type === NodeType::Condition) {
            throw new FlowException('Cannot shift-delete a condition (use cascade)');
        }

        $incoming = $flow->incoming($nodeId);
        $outgoing = $flow->outgoing($nodeId);
        if (count($outgoing) > 1) {
            throw new FlowException("Node {$nodeId} has multiple outbound edges (unexpected)");
        }

        $nodes = array_values(array_filter($flow->nodes, fn (Node $n) => $n->id !== $nodeId));
        $edges = array_values($flow->edges);

        if (count($outgoing) === 1) {
            $childId = $outgoing[0]->target;
            $outId = $outgoing[0]->id;
            $edges = array_values(array_filter($edges, fn (Edge $e) => $e->id !== $outId));

            if ($incoming !== null) {
                $edges = array_map(
                    fn (Edge $e) => $e->id === $incoming->id ? $e->withTarget($childId) : $e,
                    $edges
                );
            }
            // else: $nodeId was orphan-rooted (shouldn't happen given invariants), but child becomes orphan — fail-fast in Flow ctor
        } else {
            // leaf: just drop incoming edge
            if ($incoming !== null) {
                $incId = $incoming->id;
                $edges = array_values(array_filter($edges, fn (Edge $e) => $e->id !== $incId));
            }
        }

        return new Flow($nodes, $edges);
    }

    /**
     * Delete node + all descendants + every edge incident to the dropped set.
     * Refuses on trigger.
     */
    public static function deleteCascade(Flow $flow, string $nodeId): Flow
    {
        if ($nodeId === Flow::TRIGGER_ID) {
            throw new FlowException('Cannot delete the trigger');
        }
        $flow->node($nodeId); // validate exists

        $drop = array_fill_keys([$nodeId, ...$flow->descendants($nodeId)], true);

        $nodes = array_values(array_filter($flow->nodes, fn (Node $n) => ! isset($drop[$n->id])));
        $edges = array_values(array_filter(
            $flow->edges,
            fn (Edge $e) => ! isset($drop[$e->source]) && ! isset($drop[$e->target])
        ));

        return new Flow($nodes, $edges);
    }

    private static function genNodeId(): string
    {
        return 'n_'.Str::random(8);
    }

    private static function genEdgeId(): string
    {
        return 'e_'.Str::random(8);
    }
}
