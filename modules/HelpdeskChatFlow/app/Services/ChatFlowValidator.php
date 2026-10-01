<?php

namespace Modules\HelpdeskChatFlow\Services;

use Closure;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionParameters;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\Support\BranchOperators;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;
use Modules\HelpdeskChatFlow\Services\Support\SafeRegex;

/**
 * Validates a chat flow's node tree before publishing: blocking errors prevent
 * activation, warnings are advisory. Catches the common ways a flow breaks —
 * missing/duplicate start, orphan nodes, dead-end branches, broken `go_to_step`
 * targets, unreachable nodes, and references to variables never written.
 */
class ChatFlowValidator
{
    private const TERMINAL_TYPES = ['end', 'transfer', 'close', 'go_to_step', 'return'];

    private const ON_FAILURE_MODES = ['continue', 'handoff'];

    private const DEFAULT_ACTION_SAVE_TO = 'accion';

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

    private const VALIDATION_RULES = ['none', 'email', 'phone', 'number', 'order_ref', 'regex', 'enum'];

    /** @var (Closure(int): ?ChatFlow)|null */
    private readonly ?Closure $procedureLoader;

    /** @var (Closure(): ?array<string, array{write: bool, required: array<int, string>}>)|null */
    private readonly ?Closure $actionCatalogLoader;

    /** @var array<string, array{write: bool, required: array<int, string>}>|null|false */
    private array|null|false $actionCatalog = false;

    /**
     * @param  (Closure(int): ?ChatFlow)|null  $procedureLoader  Overrides how called flows are loaded (tests).
     * @param  (Closure(): ?array<string, array{write: bool, required: array<int, string>}>)|null  $actionCatalogLoader  Overrides the AI action catalog lookup (tests); null result = catalog unavailable.
     */
    public function __construct(?Closure $procedureLoader = null, ?Closure $actionCatalogLoader = null)
    {
        $this->procedureLoader = $procedureLoader;
        $this->actionCatalogLoader = $actionCatalogLoader;
    }

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

            if ($type === 'collect_input') {
                array_push($errors, ...$this->collectInputErrors($node, $label($node)));
            }

            if ($type === 'branchItem') {
                array_push($errors, ...$this->branchItemErrors($node, $label($node)));
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

            if ($type === 'call_flow') {
                [$callErrors, $callWarnings] = $this->callFlowIssues($node, $label($node), $flow);
                array_push($errors, ...$callErrors);
                array_push($warnings, ...$callWarnings);
            }

            if ($type === 'ai_action') {
                [$actionErrors, $actionWarnings] = $this->aiActionIssues($node, $label($node));
                array_push($errors, ...$actionErrors);
                array_push($warnings, ...$actionWarnings);
            }

            if ($type === 'return' && $flow->trigger_type !== ChatFlow::TRIGGER_PROCEDURE) {
                $warnings[] = "El nodo «{$label($node)}» solo vuelve a un flow llamante dentro de un procedimiento; aquí terminará la conversación.";
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
        $prefixes = $this->definedPrefixes($nodes);
        foreach ($this->referencedVariables($nodes) as $var) {
            if ($this->isVariableDefined(ContextPath::root($var), $defined, $prefixes)) {
                continue;
            }
            $warnings[] = "La variable «{{{$var}}}» se usa pero no se define en ningún paso anterior.";
        }

        foreach ($this->conditionVariables($nodes) as $var) {
            if ($this->isVariableDefined(ContextPath::root($var), $defined, $prefixes)) {
                continue;
            }
            $warnings[] = "La condición usa la variable «{$var}», que no se define en ningún paso anterior.";
        }

        // Seguridad: si el flow expone datos de pedidos (nodo order_lookup o un
        // ai_agent con su tool de pedidos activa) y a la vez desactiva el OTP en la
        // identificación (require_otp=false), un visitante podría consultar pedidos
        // ajenos con solo un email/teléfono adivinable.
        $exposesOrders = (bool) array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'order_lookup'
            || (($n['type'] ?? '') === 'ai_agent' && filter_var($n['data']['tool_order_lookup'] ?? true, FILTER_VALIDATE_BOOLEAN)));
        $otpDisabled = (bool) array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'identify_customer'
            && ! filter_var($n['data']['require_otp'] ?? true, FILTER_VALIDATE_BOOLEAN));
        if ($exposesOrders && $otpDisabled) {
            $warnings[] = 'El flow expone datos de pedidos con la verificación OTP desactivada (require_otp=false) en la identificación: un visitante podría consultar pedidos ajenos con un email o teléfono adivinable. Reactiva el OTP salvo que sea un flujo de bajo riesgo.';
        }

        return ['errors' => array_values($errors), 'warnings' => array_values(array_unique($warnings))];
    }

    /**
     * Regex patterns written by the designer (collect_input `regex` validation
     * and branch conditions with the `regex` operator) that do not compile or
     * could hang the server. Cheap enough to run on every save, so a bad
     * pattern never reaches the database.
     *
     * @param  array<int, mixed>  $nodes
     * @return array<int, string>
     */
    public function regexErrors(array $nodes): array
    {
        $errors = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = $node['data'] ?? [];
            $label = $node['label'] ?? ($node['type'] ?? 'nodo');

            if (($node['type'] ?? '') === 'collect_input' && ($data['validation'] ?? '') === 'regex') {
                $problem = SafeRegex::problem((string) ($data['pattern'] ?? ''));
                if ($problem !== null) {
                    $errors[] = "El paso «{$label}» tiene una expresión regular no válida: {$problem}.";
                }
            }

            if (($node['type'] ?? '') !== 'branchItem') {
                continue;
            }

            foreach ($data['conditions'] ?? [] as $condition) {
                if (! is_array($condition) || ($condition['operator'] ?? '') !== 'regex') {
                    continue;
                }

                $problem = SafeRegex::problem((string) ($condition['value'] ?? ''));
                if ($problem !== null) {
                    $errors[] = "Una condición de la rama «{$label}» tiene una expresión regular no válida: {$problem}.";
                }
            }
        }

        return $errors;
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
     * Variable names or dot paths ({{pedido.estado}}) referenced as {{var}} anywhere in the node data.
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
            if (preg_match_all('/\{\{('.ContextPath::REFERENCE.')\}\}/', $value, $matches)) {
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

            foreach ($this->nodeOutputVariables($node) as $key) {
                $defined[$key] = true;
            }
        }

        return array_keys($defined);
    }

    /**
     * Variables a node writes that don't follow the generic keys above:
     * ai_action ({save_to}, {save_to}_ok, {save_to}_status) and the `output`
     * list of call_flow (what the procedure leaves set for the caller).
     *
     * @param  array<string, mixed>  $node
     * @return array<int, string>
     */
    private function nodeOutputVariables(array $node): array
    {
        $data = $node['data'] ?? [];

        if (($node['type'] ?? '') === 'ai_action') {
            $saveTo = $this->actionSaveTo($data);

            return [$saveTo, $saveTo.'_ok', $saveTo.'_status'];
        }

        if (($node['type'] ?? '') === 'call_flow') {
            return array_values(array_filter(
                (array) ($data['output'] ?? []),
                fn ($name) => is_string($name) && $name !== '',
            ));
        }

        return [];
    }

    /**
     * Prefixes of the per-field variables ai_action flattens its result into
     * ({save_to}_{field}); the field names depend on the action, so any
     * variable starting with the prefix counts as defined.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    private function definedPrefixes(array $nodes): array
    {
        $prefixes = [];
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === 'ai_action') {
                $prefixes[$this->actionSaveTo($node['data'] ?? []).'_'] = true;
            }
        }

        return array_keys($prefixes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function actionSaveTo(array $data): string
    {
        $saveTo = trim((string) ($data['save_to'] ?? ''));

        return $saveTo !== '' ? $saveTo : self::DEFAULT_ACTION_SAVE_TO;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{0: array<int, string>, 1: array<int, string>} [errors, warnings]
     */
    private function callFlowIssues(array $node, string $label, ChatFlow $flow): array
    {
        $data = $node['data'] ?? [];
        $errors = [];
        $warnings = [];
        $flowId = (int) ($data['flow_id'] ?? 0);

        if ($flowId <= 0) {
            $errors[] = "El paso «{$label}» no indica el procedimiento que debe llamar.";
        } elseif ($flow->id !== null && $flowId === (int) $flow->id) {
            $errors[] = "El paso «{$label}» llama al propio flow; un flow no puede llamarse a sí mismo.";
        } else {
            $procedure = $this->loadProcedure($flowId);

            if ($procedure === null) {
                $errors[] = "El paso «{$label}» llama a un flow que no existe (#{$flowId}).";
            } elseif ($procedure->trigger_type !== ChatFlow::TRIGGER_PROCEDURE) {
                $errors[] = "El paso «{$label}» llama a «{$procedure->name}», que no es un procedimiento (su activación no es «procedure»).";
            } elseif ($procedure->status !== 'active') {
                $warnings[] = "El procedimiento «{$procedure->name}» llamado por «{$label}» no está activo; hasta publicarlo la llamada se omitirá.";
            }
        }

        $onMissing = (string) ($data['on_missing'] ?? 'continue');
        if (! in_array($onMissing, self::ON_FAILURE_MODES, true)) {
            $errors[] = "El paso «{$label}» tiene un valor no válido en «si el procedimiento falta» («{$onMissing}»); usa «continue» o «handoff».";
        }

        return [$errors, $warnings];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{0: array<int, string>, 1: array<int, string>} [errors, warnings]
     */
    private function aiActionIssues(array $node, string $label): array
    {
        $data = $node['data'] ?? [];
        $errors = [];
        $warnings = [];
        $key = trim((string) ($data['action_key'] ?? ''));

        $onError = (string) ($data['on_error'] ?? 'continue');
        if (! in_array($onError, self::ON_FAILURE_MODES, true)) {
            $errors[] = "El paso «{$label}» tiene un valor no válido en «si la acción falla» («{$onError}»); usa «continue» o «handoff».";
        }

        if ($key === '') {
            $errors[] = "El paso «{$label}» no indica qué acción del catálogo ejecutar.";

            return [$errors, $warnings];
        }

        $catalog = $this->actionCatalog();
        if ($catalog === null) {
            return [$errors, $warnings];
        }

        if (! isset($catalog[$key])) {
            $errors[] = "El paso «{$label}» usa la acción «{$key}», que no existe o no está activa en el catálogo.";

            return [$errors, $warnings];
        }

        if ($catalog[$key]['write'] && trim((string) ($data['confirmed_variable'] ?? '')) === '') {
            $errors[] = "La acción «{$key}» modifica datos: el paso «{$label}» debe indicar la variable que guarda la confirmación del cliente.";
        }

        $args = (array) ($data['args'] ?? []);
        foreach ($catalog[$key]['required'] as $param) {
            if (trim((string) ($args[$param] ?? '')) === '') {
                $warnings[] = "El paso «{$label}» no rellena el parámetro obligatorio «{$param}» de la acción «{$key}».";
            }
        }

        return [$errors, $warnings];
    }

    private function loadProcedure(int $flowId): ?ChatFlow
    {
        if ($this->procedureLoader !== null) {
            return ($this->procedureLoader)($flowId);
        }

        return ChatFlow::query()->find($flowId);
    }

    /**
     * Active catalog actions keyed by `key`, or null when the catalog can't be
     * read (HelpdeskAiPrompts absent/disabled, no database): then the key is
     * not checked rather than reported as missing.
     *
     * @return array<string, array{write: bool, required: array<int, string>}>|null
     */
    private function actionCatalog(): ?array
    {
        if ($this->actionCatalog === false) {
            $this->actionCatalog = $this->actionCatalogLoader !== null
                ? ($this->actionCatalogLoader)()
                : $this->readActionCatalog();
        }

        return $this->actionCatalog;
    }

    /**
     * @return array<string, array{write: bool, required: array<int, string>}>|null
     */
    private function readActionCatalog(): ?array
    {
        if (! class_exists(AiAction::class)) {
            return null;
        }

        try {
            $catalog = [];

            foreach (AiAction::query()->where('is_active', true)->get(['key', 'type', 'parameters', 'config', 'rules']) as $action) {
                $definition = $action->only(['type', 'parameters', 'config', 'rules']);
                $required = collect(ActionParameters::effective($definition, false))
                    ->filter(fn (array $param): bool => ! empty($param['required']) && $param['name'] !== ActionParameters::CONFIRM)
                    ->pluck('name')
                    ->all();

                $catalog[$action->key] = [
                    'write' => ActionParameters::needsConfirmation($definition),
                    'required' => $required,
                ];
            }

            return $catalog;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, string>
     */
    private function collectInputErrors(array $node, string $label): array
    {
        $data = $node['data'] ?? [];
        $rule = (string) ($data['validation'] ?? 'none');
        $errors = [];

        if (! in_array($rule, self::VALIDATION_RULES, true)) {
            $errors[] = "El paso «{$label}» usa una validación desconocida («{$rule}»).";
        }

        if ($rule === 'regex') {
            $problem = SafeRegex::problem((string) ($data['pattern'] ?? ''));
            if ($problem !== null) {
                $errors[] = "El paso «{$label}» tiene una expresión regular no válida: {$problem}.";
            }
        }

        if ($rule === 'enum') {
            $allowed = array_filter(
                is_array($data['allowed'] ?? null) ? $data['allowed'] : [],
                fn ($option) => is_scalar($option) && trim((string) $option) !== '',
            );
            if ($allowed === []) {
                $errors[] = "El paso «{$label}» valida contra una lista, pero la lista de valores permitidos está vacía.";
            }
        }

        if (filter_var($data['skip_if_set'] ?? false, FILTER_VALIDATE_BOOLEAN) && trim((string) ($data['variable_name'] ?? '')) === '') {
            $errors[] = "El paso «{$label}» tiene «saltar si ya se sabe» pero no indica la variable donde guarda la respuesta.";
        }

        return $errors;
    }

    /**
     * Operator and value of every condition of a branch item, plus its all/any mode.
     *
     * @param  array<string, mixed>  $node
     * @return array<int, string>
     */
    private function branchItemErrors(array $node, string $label): array
    {
        $data = $node['data'] ?? [];

        if ($data['isElse'] ?? false) {
            return [];
        }

        $errors = [];
        $match = strtolower((string) ($data['match'] ?? 'all'));

        if (! in_array($match, ['all', 'any'], true)) {
            $errors[] = "La rama «{$label}» tiene un modo de combinación no válido («{$match}»); usa «all» o «any».";
        }

        foreach ($data['conditions'] ?? [] as $index => $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $position = $index + 1;
            $operator = (string) ($condition['operator'] ?? '=');

            if (trim((string) ($condition['variable'] ?? '')) === '') {
                $errors[] = "La condición {$position} de la rama «{$label}» no indica la variable.";
            }

            if (! BranchOperators::isValid($operator)) {
                $errors[] = "La condición {$position} de la rama «{$label}» usa un operador desconocido («{$operator}»).";

                continue;
            }

            $problem = BranchOperators::valueProblem($condition);
            if ($problem !== null) {
                $errors[] = "La condición {$position} de la rama «{$label}» no es válida: {$problem}.";
            }
        }

        return $errors;
    }

    /**
     * Variables (or dot paths) read by branch conditions.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    private function conditionVariables(array $nodes): array
    {
        $found = [];
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') !== 'branchItem') {
                continue;
            }
            foreach ($node['data']['conditions'] ?? [] as $condition) {
                $variable = trim((string) (is_array($condition) ? $condition['variable'] ?? '' : ''));
                $variable = trim(preg_replace('/^\{\{(.*)\}\}$/s', '$1', $variable) ?? $variable);
                if ($variable !== '') {
                    $found[$variable] = true;
                }
            }
        }

        return array_keys($found);
    }

    /**
     * @param  array<int, string>  $defined
     * @param  array<int, string>  $definedPrefixes
     */
    private function isVariableDefined(string $var, array $defined, array $definedPrefixes = []): bool
    {
        if (in_array($var, self::SYSTEM_VARIABLES, true) || in_array($var, $defined, true)) {
            return true;
        }

        foreach ($definedPrefixes as $prefix) {
            if (str_starts_with($var, $prefix)) {
                return true;
            }
        }

        foreach (self::SYSTEM_VARIABLE_PREFIXES as $prefix) {
            if (str_starts_with($var, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
