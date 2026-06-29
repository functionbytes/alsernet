<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Validates a chat flow's node tree before publishing: blocking errors prevent
 * activation, warnings are advisory. Catches the common ways a flow breaks —
 * missing/duplicate start, orphan nodes, dead-end branches.
 */
class ChatFlowValidator
{
    private const TERMINAL_TYPES = ['end', 'transfer', 'close', 'go_to_step'];

    // Nodes exempt from the dead-end warning: they either wait for customer
    // input or are flow-control nodes whose continuation may live in branches.
    private const WAIT_TYPES = ['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat', 'rich_message', 'business_hours'];

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
        }

        return ['errors' => array_values($errors), 'warnings' => array_values($warnings)];
    }
}
