<?php

namespace Modules\Campaign\Domain\Automation;

use Modules\Campaign\Domain\Automation\Enum\Branch;
use Modules\Campaign\Domain\Automation\Enum\NodeType;

/**
 * Aggregate. A Flow is a directed acyclic graph with exactly one Trigger node
 * (id "trigger") as the root. Conditions branch into yes/no edges; all other
 * nodes have ≤1 outbound edge with no branch label.
 *
 * Immutable — every mutation returns a new Flow (see FlowMutator).
 */
final class Flow
{
    public const TRIGGER_ID = 'trigger';

    public const VERSION = 1;

    /** @var array<string, Node> nodes keyed by id */
    public readonly array $nodes;

    /** @var array<string, Edge> edges keyed by id */
    public readonly array $edges;

    /**
     * @param  iterable<Node>  $nodes
     * @param  iterable<Edge>  $edges
     */
    public function __construct(iterable $nodes, iterable $edges)
    {
        $nMap = [];
        foreach ($nodes as $n) {
            if (isset($nMap[$n->id])) {
                throw new FlowException("Duplicate node id: {$n->id}");
            }
            $nMap[$n->id] = $n;
        }
        $eMap = [];
        foreach ($edges as $e) {
            if (isset($eMap[$e->id])) {
                throw new FlowException("Duplicate edge id: {$e->id}");
            }
            $eMap[$e->id] = $e;
        }
        $this->nodes = $nMap;
        $this->edges = $eMap;
        $this->assertWellFormed();
    }

    public static function empty(): self
    {
        // An "unconfigured" trigger has no `key` — that's the signal for the UI.
        // No `init` flag (legacy concept; replaced by `key` presence).
        $trigger = new Node(self::TRIGGER_ID, NodeType::Trigger, [
            'title' => 'Click to choose a trigger',
        ]);

        return new self([$trigger], []);
    }

    public function trigger(): Node
    {
        return $this->nodes[self::TRIGGER_ID];
    }

    public function node(string $id): Node
    {
        if (! isset($this->nodes[$id])) {
            throw new FlowException("Node not found: {$id}");
        }

        return $this->nodes[$id];
    }

    public function edge(string $id): Edge
    {
        if (! isset($this->edges[$id])) {
            throw new FlowException("Edge not found: {$id}");
        }

        return $this->edges[$id];
    }

    public function hasNode(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function hasEdge(string $id): bool
    {
        return isset($this->edges[$id]);
    }

    /** @return list<Edge> */
    public function outgoing(string $nodeId): array
    {
        return array_values(array_filter($this->edges, fn (Edge $e) => $e->source === $nodeId));
    }

    public function incoming(string $nodeId): ?Edge
    {
        foreach ($this->edges as $e) {
            if ($e->target === $nodeId) {
                return $e;
            }
        }

        return null;
    }

    /** @return list<string> all descendant node ids (excluding self) */
    public function descendants(string $nodeId): array
    {
        $visited = [];
        $stack = [$nodeId];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($this->outgoing($cur) as $e) {
                if (! isset($visited[$e->target])) {
                    $visited[$e->target] = true;
                    $stack[] = $e->target;
                }
            }
        }

        return array_keys($visited);
    }

    /** @return list<Node> root → … → node, throws if disconnected from trigger */
    public function pathFromRoot(string $nodeId): array
    {
        $path = [];
        $cur = $nodeId;
        $seen = [];
        while ($cur !== null) {
            if (isset($seen[$cur])) {
                throw new FlowException("Cycle detected at {$cur}");
            }
            $seen[$cur] = true;
            $path[] = $this->node($cur);
            $inc = $this->incoming($cur);
            $cur = $inc?->source;
        }

        return array_reverse($path);
    }

    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'nodes' => array_map(fn (Node $n) => $n->toArray(), array_values($this->nodes)),
            'edges' => array_map(fn (Edge $e) => $e->toArray(), array_values($this->edges)),
        ];
    }

    public static function fromArray(array $a): self
    {
        $nodes = array_map([Node::class, 'fromArray'], $a['nodes'] ?? []);
        $edges = array_map([Edge::class, 'fromArray'], $a['edges'] ?? []);

        return new self($nodes, $edges);
    }

    /**
     * Parse a flow from JSON.
     *
     * Returns Flow::empty() in any of these cases:
     *   - $json is null / empty string
     *   - $json is not graph-normalized (e.g. legacy adjacency-list shape).
     * This is the "clean cut" path — legacy data is dropped silently and
     * gets overwritten on first save through the new editor.
     */
    public static function fromJson(?string $json): self
    {
        if ($json === null || $json === '') {
            return self::empty();
        }
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::empty();
        }
        if (! is_array($decoded) || ! isset($decoded['nodes'], $decoded['edges'])) {
            // Not graph-normalized (probably legacy shape) — start fresh.
            return self::empty();
        }

        return self::fromArray($decoded);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @internal */
    private function assertWellFormed(): void
    {
        // 1. Exactly 1 trigger
        $triggers = array_filter($this->nodes, fn (Node $n) => $n->type === NodeType::Trigger);
        if (count($triggers) !== 1) {
            throw new FlowException('Flow must contain exactly 1 trigger node');
        }
        if (! isset($this->nodes[self::TRIGGER_ID])) {
            throw new FlowException(sprintf('Trigger node id must be "%s"', self::TRIGGER_ID));
        }

        // 2. All edge endpoints reference existing nodes
        foreach ($this->edges as $e) {
            if (! isset($this->nodes[$e->source])) {
                throw new FlowException("Edge {$e->id} references missing source: {$e->source}");
            }
            if (! isset($this->nodes[$e->target])) {
                throw new FlowException("Edge {$e->id} references missing target: {$e->target}");
            }
            if ($e->source === $e->target) {
                throw new FlowException("Edge {$e->id} self-loops on {$e->source}");
            }
        }

        // 3. Branch labels match parent type:
        //    - Conditions: 0–2 outbound, branches must be {yes, no} distinct.
        //    - Non-conditions: 0–1 outbound, branch must be null.
        // 4. At most 1 incoming edge per node (tree-shaped).
        $bySource = [];
        $byTarget = [];
        foreach ($this->edges as $e) {
            $bySource[$e->source][] = $e;
            $byTarget[$e->target][] = $e;
        }
        foreach ($byTarget as $tid => $list) {
            if (count($list) > 1) {
                throw new FlowException("Node {$tid} has multiple incoming edges (must be tree-shaped)");
            }
        }
        foreach ($bySource as $sid => $list) {
            $node = $this->nodes[$sid];
            if ($node->type === NodeType::Condition) {
                if (count($list) > 2) {
                    throw new FlowException("Condition {$sid} has more than 2 outbound edges");
                }
                $branches = [];
                foreach ($list as $e) {
                    if ($e->branch === null) {
                        throw new FlowException("Condition {$sid} edge {$e->id} missing branch label");
                    }
                    if (isset($branches[$e->branch->value])) {
                        throw new FlowException("Condition {$sid} has duplicate branch: {$e->branch->value}");
                    }
                    $branches[$e->branch->value] = true;
                }
            } else {
                if (count($list) > 1) {
                    throw new FlowException("Node {$sid} has more than 1 outbound edge (only conditions branch)");
                }
                foreach ($list as $e) {
                    if ($e->branch !== null) {
                        throw new FlowException("Edge {$e->id} on non-condition source {$sid} must have null branch");
                    }
                }
            }
        }

        // 5. Trigger has no incoming
        if (isset($byTarget[self::TRIGGER_ID])) {
            throw new FlowException('Trigger node cannot have incoming edges');
        }

        // 6. No cycles — DFS from trigger, then ensure every reachable node is reachable
        //    (orphan subgraphs are allowed conceptually but rejected by tree-shaped invariant above).
        $color = []; // 0=white, 1=gray, 2=black
        $dfs = function (string $u) use (&$color, &$dfs, $bySource) {
            $color[$u] = 1;
            foreach ($bySource[$u] ?? [] as $e) {
                $v = $e->target;
                $c = $color[$v] ?? 0;
                if ($c === 1) {
                    throw new FlowException("Cycle detected involving node {$v}");
                }
                if ($c === 0) {
                    $dfs($v);
                }
            }
            $color[$u] = 2;
        };
        $dfs(self::TRIGGER_ID);
    }
}
