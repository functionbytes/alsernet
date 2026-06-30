<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Validates a chat flow's node tree before publishing: blocking errors prevent
 * activation, warnings are advisory. Catches the common ways a flow breaks —
 * missing/duplicate start, orphan nodes, dead-end branches, broken `go_to_step`
 * targets, unreachable nodes, and references to variables never written.
 */
class ChatFlowValidator
{
    private const TERMINAL_TYPES = ['end', 'transfer', 'close', 'go_to_step'];

    // Nodes exempt from the dead-end warning: they either wait for customer
    // input or are flow-control nodes whose continuation may live in branches.
    private const WAIT_TYPES = ['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat', 'rich_message', 'business_hours'];

    // Variables the engine seeds at runtime regardless of the node tree, so a
    // reference to them is never a "never written" mistake. Plus the dynamic
    // prefixes produced by identify_customer (customer_*) and order_lookup (order_*).
    private const SYSTEM_VARIABLES = [
        'last_input', 'customer_lang', 'customer_sentiment', 'customer_identified',
        'customer_name', 'customer_email', 'within_business_hours', 'csat_score',
        'order_found', 'order_id', 'order_date', 'order_status', 'order_total',
        'order_tracking', 'ai_used_kb', 'uploaded_docs', 'doc_upload_url', 'doc_missing',
    ];

    private const SYSTEM_VARIABLE_PREFIXES = ['customer_', 'order_', '_'];

    /**
     * @return array{errors: array<int, string>, warnings: array<int, string>}
     */
    public function validate(ChatFlow $flow): array
    {
        $nodes = $flow->nodes ?? [];
        $errors = [];
        $warnings = [];

        if (empty($nodes)) {
            return ['errors' => ['El flow no tiene nodos.'], 'warnings' => []];
        }

        $ids = array_map(fn ($n) => $n['id'] ?? null, $nodes);
        $label = fn ($n) => $n['label'] ?? ($n['type'] ?? 'nodo');

        // Single start node.
        $starts = array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'start');
        if (count($starts) === 0) {
            $errors[] = 'El flow no tiene nodo de inicio.';
        } elseif (count($starts) > 1) {
            $errors[] = 'El flow tiene más de un nodo de inicio.';
        }

        // Unique ids.
        $duplicates = array_unique(array_diff_assoc($ids, array_unique($ids)));
        foreach ($duplicates as $dup) {
            $errors[] = "Hay nodos con el mismo identificador: «{$dup}».";
        }

        foreach ($nodes as $node) {
            $parentId = $node['parentId'] ?? null;

            // Orphan: parent referenced but missing.
            if ($parentId !== null && ! in_array($parentId, $ids, true)) {
                $errors[] = "El nodo «{$label($node)}» apunta a un padre que no existe.";
            }

            $type = $node['type'] ?? '';
            $children = array_filter($nodes, fn ($n) => ($n['parentId'] ?? null) === ($node['id'] ?? null) && ($n['type'] ?? '') !== 'branchItem');

            // Dead-end: a non-terminal, non-wait node with no children completes
            // the session silently — usually a design mistake. `branches` is exempt:
            // its continuation lives in the branchItem children.
            if (! in_array($type, self::TERMINAL_TYPES, true)
                && ! in_array($type, self::WAIT_TYPES, true)
                && $type !== 'branchItem'
                && $type !== 'branches'
                && empty($children)) {
                $warnings[] = "El nodo «{$label($node)}» no tiene continuación; el flow terminará ahí.";
            }

            // A branches node without a branchItem marked as "else" may drop messages.
            if ($type === 'branches') {
                $items = array_filter($nodes, fn ($n) => ($n['parentId'] ?? null) === ($node['id'] ?? null) && ($n['type'] ?? '') === 'branchItem');
                $hasElse = (bool) array_filter($items, fn ($n) => $n['data']['isElse'] ?? false);
                if (! $hasElse) {
                    $warnings[] = "La condición «{$label($node)}» no tiene rama «si no» (else); algunos clientes podrían quedarse sin respuesta.";
                }
            }

            // go_to_step must point to an existing node, or the engine fails the
            // session at runtime when it resolves the missing target.
            if ($type === 'go_to_step') {
                $target = $node['data']['target_node_id'] ?? null;
                if ($target === null || $target === '') {
                    $warnings[] = "El salto «{$label($node)}» no tiene paso de destino configurado.";
                } elseif (! in_array($target, $ids, true)) {
                    $errors[] = "El salto «{$label($node)}» apunta a un paso que no existe.";
                }
            }
        }

        // Nodes that can never be reached from start are dead config — flag them so
        // the designer notices before publishing.
        if (count($starts) === 1) {
            $start = reset($starts);
            foreach ($this->unreachableNodes($nodes, $start['id'] ?? null) as $node) {
                $warnings[] = "El nodo «{$label($node)}» no es alcanzable desde el inicio del flow.";
            }
        }

        // Variables referenced via {{var}} but never written stay literal at runtime.
        $defined = $this->definedVariables($nodes);
        foreach ($this->referencedVariables($nodes) as $var) {
            if ($this->isVariableDefined($var, $defined)) {
                continue;
            }
            $warnings[] = "La variable «{{{$var}}}» se usa pero no se define en ningún paso anterior.";
        }

        return ['errors' => array_values($errors), 'warnings' => array_values(array_unique($warnings))];
    }

    /**
     * Nodes that cannot be reached from the start node by walking parent→child
     * edges and `go_to_step` jumps.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function unreachableNodes(array $nodes, mixed $startId): array
    {
        if ($startId === null) {
            return [];
        }

        $childrenByParent = [];
        $nodesById = [];
        foreach ($nodes as $node) {
            $nodesById[$node['id'] ?? ''] = $node;
            $parent = $node['parentId'] ?? null;
            if ($parent !== null) {
                $childrenByParent[$parent][] = $node['id'] ?? null;
            }
        }

        $visited = [];
        $queue = [$startId];
        while ($queue) {
            $current = array_shift($queue);
            if ($current === null || isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            foreach ($childrenByParent[$current] ?? [] as $child) {
                if ($child !== null && ! isset($visited[$child])) {
                    $queue[] = $child;
                }
            }

            $target = $nodesById[$current]['data']['target_node_id'] ?? null;
            if ($target !== null && $target !== '' && ! isset($visited[$target])) {
                $queue[] = $target;
            }
        }

        return array_values(array_filter(
            $nodes,
            fn ($n) => ! isset($visited[$n['id'] ?? '']),
        ));
    }

    /**
     * Variable names referenced as {{var}} anywhere in the node data.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    private function referencedVariables(array $nodes): array
    {
        $found = [];
        array_walk_recursive($nodes, function ($value) use (&$found) {
            if (! is_string($value)) {
                return;
            }
            if (preg_match_all('/\{\{(\w+)\}\}/', $value, $matches)) {
                foreach ($matches[1] as $name) {
                    $found[$name] = true;
                }
            }
        });

        return array_keys($found);
    }

    /**
     * Variable names a node writes to the session context (collect_input, csat,
     * request_documents, ai_response, set_attribute).
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    private function definedVariables(array $nodes): array
    {
        $defined = [];
        foreach ($nodes as $node) {
            $data = $node['data'] ?? [];
            $keys = array_filter([
                $data['variable_name'] ?? null,
                $data['save_to'] ?? null,
                $data['custom_key'] ?? null,
                $data['attribute'] ?? null,
            ], fn ($k) => is_string($k) && $k !== '');

            foreach ($keys as $key) {
                $defined[$key] = true;
            }
        }

        return array_keys($defined);
    }

    /**
     * @param  array<int, string>  $defined
     */
    private function isVariableDefined(string $var, array $defined): bool
    {
        if (in_array($var, self::SYSTEM_VARIABLES, true) || in_array($var, $defined, true)) {
            return true;
        }

        foreach (self::SYSTEM_VARIABLE_PREFIXES as $prefix) {
            if (str_starts_with($var, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
