<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Str;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Flow\AiActionNodeHandler;
use Modules\HelpdeskChatFlow\Services\Concerns\EvaluatesBranchConditions;
use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;
use Modules\HelpdeskChatFlow\Services\Concerns\ValidatesUserInput;
use Modules\HelpdeskChatFlow\Services\Nodes\FlowCallNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandlerRegistry;
use Modules\HelpdeskChatFlow\Services\Simulation\SimulatedChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Simulation\SimulatedConversation;
use Modules\HelpdeskChatFlow\Services\Simulation\TestModeActionExecutor;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;

/**
 * Editor test panel. What the customer sees is rendered by the SAME node
 * handlers production uses (HANDLER_RENDERED_TYPES), run against an in-memory
 * session/conversation. Nodes with external side effects (AI, order lookup,
 * HTTP, tickets, tags, handoff…) are simulated and annotated instead.
 */
class ChatFlowTestSimulator
{
    use EvaluatesBranchConditions, FormatsNumberedOptions, ValidatesUserInput;

    /** Rendered by the production NodeHandler (no side effects outside the conversation). */
    private const HANDLER_RENDERED_TYPES = ['message', 'quick_replies', 'collect_input', 'identify_customer', 'request_documents', 'csat', 'rich_message', 'send_file', 'business_hours'];

    /** Simulated here: routing, or nodes whose real effect must not happen in a test. */
    private const SIMULATED_TYPES = ['start', 'branches', 'branchItem', 'delay', 'go_to_step', 'action', 'add_tag', 'set_attribute', 'ai_response', 'ai_agent', 'order_lookup', 'http_request', 'document_link', 'create_ticket', 'transfer', 'close', 'end', 'call_flow', 'return', 'ai_action'];

    /**
     * @param  NodeHandler|null  $aiActionHandler  Overrides the test-mode ai_action handler (tests).
     */
    public function __construct(
        private readonly ?NodeHandlerRegistry $handlers = null,
        private readonly ?NodeHandler $aiActionHandler = null,
    ) {}

    /**
     * Node types the simulator knows how to run (a type outside this list is
     * skipped with a visible warning instead of silently ending the test).
     *
     * @return array<int, string>
     */
    public static function supportedTypes(): array
    {
        return [...self::HANDLER_RENDERED_TYPES, ...self::SIMULATED_TYPES];
    }

    private const WAIT_TYPES = ['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat'];

    private const TERMINAL_TYPES = ['end', 'transfer', 'close'];

    private const MAX_DEPTH = 30;

    private const TTL = 1800;

    /**
     * @param  int|null  $flowId  Id of the flow being tested (so a procedure can't call it back).
     */
    public function start(array $nodes, ?int $userId = null, ?int $flowId = null): array
    {
        $startNode = collect($nodes)->first(fn ($n) => ($n['type'] ?? '') === 'start');

        if (! $startNode) {
            return ['error' => 'El flow no tiene nodo de inicio.'];
        }

        $sessionKey = Str::uuid()->toString();

        $session = [
            'nodes' => $nodes,
            'context' => [],
            'current_node_id' => null,
            'status' => 'active',
            'flow_id' => $flowId,
            // Draft nodes of each flow waiting for a procedure to return, innermost last.
            'call_stack' => [],
        ];

        [$messages, $session] = $this->runFrom($session, $startNode['id']);

        cache()->put($this->cacheKey($sessionKey, $userId), $session, self::TTL);

        return ['session_key' => $sessionKey, 'messages' => $messages, 'status' => $session['status']];
    }

    public function replyWithFile(string $sessionKey, string $docKey, string $fileName, ?int $userId = null): array
    {
        $key = $this->cacheKey($sessionKey, $userId);
        $session = cache()->get($key);

        if (! $session) {
            return ['error' => 'Sesión expirada. Reinicia el chat de prueba.'];
        }

        if ($session['status'] !== 'active') {
            return ['messages' => [], 'status' => $session['status']];
        }

        $currentNode = $this->getNode($session, $session['current_node_id']);

        if (! $currentNode || $currentNode['type'] !== 'request_documents') {
            return ['error' => 'El nodo actual no acepta archivos.'];
        }

        // Pass the doc key directly — processDocumentUpload matches raw keys too
        [$messages, $session] = $this->processInput($session, $currentNode, $docKey);

        // Prepend the file receipt confirmation before the bot responses
        array_unshift($messages, [
            'type' => 'bot',
            'text' => '📎 Archivo recibido: <strong>'.e($fileName).'</strong>',
        ]);

        cache()->put($key, $session, self::TTL);

        return ['messages' => $messages, 'status' => $session['status']];
    }

    public function reply(string $sessionKey, string $userMessage, ?int $userId = null): array
    {
        $key = $this->cacheKey($sessionKey, $userId);
        $session = cache()->get($key);

        if (! $session) {
            return ['error' => 'Sesión expirada. Reinicia el chat de prueba.'];
        }

        if ($session['status'] !== 'active') {
            return ['messages' => [], 'status' => $session['status']];
        }

        $currentNode = $this->getNode($session, $session['current_node_id']);

        if (! $currentNode) {
            return ['error' => 'Nodo actual no encontrado.'];
        }

        [$messages, $session] = $this->processInput($session, $currentNode, $userMessage);

        cache()->put($key, $session, self::TTL);

        return ['messages' => $messages, 'status' => $session['status']];
    }

    /**
     * Scopes the cache key to the owning user so a test session started by one
     * user cannot be driven (incurring OpenAI / external-HTTP cost) by another.
     */
    private function cacheKey(string $sessionKey, ?int $userId): string
    {
        return $userId !== null
            ? "chatflow_test:{$userId}:{$sessionKey}"
            : "chatflow_test:{$sessionKey}";
    }

    // ─── Core execution ────────────────────────────────────────────────────────

    private function runFrom(array $session, string $nodeId): array
    {
        $messages = [];
        $currentId = $nodeId;
        $depth = 0;

        while ($currentId && $depth < self::MAX_DEPTH) {
            $node = $this->getNode($session, $currentId);

            if (! $node) {
                break;
            }

            $session['current_node_id'] = $currentId;

            if (in_array($node['type'], self::TERMINAL_TYPES)) {
                [$termMessages, $termStatus] = $this->buildTerminalMessages($node);
                $messages = array_merge($messages, $termMessages);
                $session['status'] = $termStatus;
                break;
            }

            if ($node['type'] === 'collect_input' && $this->shouldSkipCollectInput($node['data'] ?? [], $session['context'] ?? [])) {
                $messages[] = ['type' => 'bot', 'text' => '⏭ [Pregunta omitida: «'.($node['data']['variable_name'] ?? '').'» ya tiene valor]', 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);

                if ($nextId === null) {
                    $session['status'] = 'completed';
                    break;
                }

                $currentId = $nextId;
                $depth++;

                continue;
            }

            $isRichWait = $node['type'] === 'rich_message' && ! empty($node['data']['options']);

            if (in_array($node['type'], self::WAIT_TYPES) || $isRichWait) {
                [$waitMessages, , $session] = $this->renderWithHandler($session, $node);
                $messages = array_merge($messages, $waitMessages, $this->waitHints($session, $node));
                break;
            }

            [$nodeMessages, $nextId, $session] = $this->executeNode($session, $node);
            $messages = array_merge($messages, $nodeMessages);

            if ($nextId === null && $this->canResumeCaller($session)) {
                [$resumeMessages, $nextId, $session] = $this->resumeCaller($session);
                $messages = array_merge($messages, $resumeMessages);
            }

            if ($nextId === null) {
                if ($session['status'] === 'active') {
                    $session['status'] = 'completed';
                }
                break;
            }

            $currentId = $nextId;
            $depth++;
        }

        return [$messages, $session];
    }

    private function executeNode(array $session, array $node): array
    {
        if (in_array($node['type'], self::HANDLER_RENDERED_TYPES, true)) {
            [$messages, $nextId, $session] = $this->renderWithHandler($session, $node);

            if ($node['type'] === 'business_hours') {
                $label = ($session['context']['within_business_hours'] ?? false) ? 'dentro de horario' : 'fuera de horario';
                $messages[] = ['type' => 'bot', 'text' => "🕒 [Horario de atención: {$label}]", 'system' => true];
            }

            return [$messages, $nextId, $session];
        }

        $messages = [];
        $nextId = null;

        switch ($node['type']) {
            case 'start':
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'action':
                $label = match ($node['data']['action_type'] ?? '') {
                    'assign_agent' => '🔀 [Acción] Asignar a agente',
                    'change_status' => '🔄 [Acción] Cambiar estado',
                    'add_tag' => '🏷️ [Acción] Agregar etiqueta',
                    default => '⚡ [Acción] '.($node['data']['action_type'] ?? ''),
                };
                $messages[] = ['type' => 'bot', 'text' => $label, 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'delay':
                $secs = (int) ($node['data']['seconds'] ?? 5);
                $messages[] = ['type' => 'bot', 'text' => "⏱ [Espera de {$secs}s omitida en modo prueba]", 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'add_tag':
                $tags = $node['data']['tags'] ?? [];
                $label = ! empty($tags) ? implode(', ', $tags) : '(sin etiquetas)';
                $messages[] = ['type' => 'bot', 'text' => "🏷️ [Etiquetas agregadas: {$label}]", 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'set_attribute':
                $attr = $node['data']['attribute'] ?? 'campo';
                $val = $node['data']['value'] ?? '';
                $key = $node['data']['custom_key'] ?? $attr;
                $messages[] = ['type' => 'bot', 'text' => "⚙️ [Atributo establecido: {$key} = {$val}]", 'system' => true];
                $session['context'][$key] = $val;
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'go_to_step':
                $targetId = $node['data']['target_node_id'] ?? null;
                $targetLabel = $node['data']['target_label'] ?? $targetId;
                $messages[] = ['type' => 'bot', 'text' => "↩️ [Ir al paso: {$targetLabel}]", 'system' => true];
                $nextId = $targetId;
                break;

            case 'ai_response':
                [$aiMessages, $session] = $this->simulateAiResponse($session, $node);
                $messages = array_merge($messages, $aiMessages);
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'ai_agent':
                $question = (string) ($session['context'][$node['data']['question_variable'] ?? 'last_input'] ?? '');
                $result = app(ChatFlowAgentService::class)->run($question, $session['context'] ?? [], $node['data'] ?? [], 'es');
                $messages[] = ['type' => 'bot', 'text' => $result['text']];
                if (! empty($result['used_tools'])) {
                    $messages[] = ['type' => 'bot', 'text' => '🛠️ [Herramientas usadas: '.implode(', ', $result['used_tools']).']', 'system' => true];
                }
                if ($result['action'] === 'escalate') {
                    $session['status'] = 'transferred';
                    $nextId = null;
                } else {
                    $nextId = $this->firstChildId($session, $node['id']);
                }
                break;

            case 'order_lookup':
                [$orderMessages, $session] = $this->simulateOrderLookup($session, $node);
                $messages = array_merge($messages, $orderMessages);
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'http_request':
                [$httpMessages, $session] = $this->simulateHttpRequest($session, $node);
                $messages = array_merge($messages, $httpMessages);
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'document_link':
                // The real node reads the conversation's document request (HelpdeskDocument).
                $session['context'] += ['doc_upload_url' => 'https://ejemplo.test/documentos?token=SIMULACION', 'doc_missing' => '', 'doc_found' => ''];
                $messages[] = ['type' => 'bot', 'text' => '📄 [Enlace al portal de documentos: {{doc_upload_url}} se genera al ejecutar]', 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'create_ticket':
                // Never create a real ticket from the test panel.
                $number = 'SIM-0001';
                $session['context']['created_ticket_number'] ??= $number;
                $subject = $this->interpolate((string) ($node['data']['subject'] ?? ''), $session['context']);
                $messages[] = ['type' => 'bot', 'text' => '🎫 [Se crearía un ticket'.($subject !== '' ? ': '.e($subject) : '').']', 'system' => true];
                $confirmation = $node['data']['confirmation'] ?? 'He creado el ticket :number para dar seguimiento a tu solicitud.';
                $messages[] = ['type' => 'bot', 'text' => e(str_replace([':number', '{{ticket_number}}'], $number, $confirmation))];
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'call_flow':
            case 'return':
                [$messages, $nextId, $session] = $this->runProcedureNode($session, $node);
                break;

            case 'ai_action':
                [$messages, $nextId, $session] = $this->simulateAiAction($session, $node);
                break;

            case 'branches':
                $nextId = $this->executeBranches($session, $node);
                break;

            case 'branchItem':
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            default:
                // A type the simulator doesn't know (e.g. contributed by another
                // module): say so and keep going instead of silently ending.
                $messages[] = ['type' => 'bot', 'text' => '⚠️ [Nodo «'.e($node['type']).'» no simulable: se omite en modo prueba]', 'system' => true];
                $nextId = $this->firstChildId($session, $node['id']);
        }

        return [$messages, $nextId, $session];
    }

    /**
     * Runs the production NodeHandler for this node against an in-memory
     * session/conversation and turns what it posted into test-panel messages.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string, 2: array<string, mixed>}
     */
    private function renderWithHandler(array $session, array $node, ?NodeHandler $handler = null): array
    {
        $conversation = new SimulatedConversation;
        $simSession = $this->simulatedSession($session, $conversation);

        $handler ??= $this->registry()->for($node['type']);
        $nextId = $handler?->handle($node, $simSession, $conversation);

        $session['context'] = $simSession->context ?? [];

        if (($simSession->status ?? 'active') !== 'active') {
            $session['status'] = $simSession->status;
        }

        return [$this->toPanelMessages($conversation->takeCaptured()), $nextId, $session];
    }

    private function registry(): NodeHandlerRegistry
    {
        return $this->handlers ?? app(NodeHandlerRegistry::class);
    }

    private function simulatedSession(array $session, SimulatedConversation $conversation): SimulatedChatFlowSession
    {
        return SimulatedChatFlowSession::for($session['nodes'], $session['context'] ?? [], $conversation, $session['flow_id'] ?? null);
    }

    // ─── Procedures (call_flow / return) ───────────────────────────────────────

    /**
     * Runs call_flow / return through the production FlowCallNodeHandler (return
     * stack, input/output variables, cycle and depth limits) and mirrors the
     * flow switch it does on the session: the caller's DRAFT nodes are kept on
     * `call_stack` so a return resumes the draft being tested, not the saved flow.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string, 2: array<string, mixed>}
     */
    private function runProcedureNode(array $session, array $node): array
    {
        $conversation = new SimulatedConversation;
        $simSession = $this->simulatedSession($session, $conversation);
        $depthBefore = $this->stackDepth($simSession);

        $nextId = $this->registry()->for($node['type'])?->handle($node, $simSession, $conversation);

        return $this->applyProcedureSwitch($session, $simSession, $depthBefore, $nextId, $node['type'] === 'call_flow');
    }

    private function canResumeCaller(array $session): bool
    {
        return $session['status'] === 'active' && ! empty($session['call_stack']);
    }

    /**
     * Natural end of a procedure (a branch with nothing left to run): back to the caller.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string, 2: array<string, mixed>}
     */
    private function resumeCaller(array $session): array
    {
        $handler = $this->registry()->for('return');

        if (! $handler instanceof FlowCallNodeHandler) {
            return [[], null, $session];
        }

        $simSession = $this->simulatedSession($session, new SimulatedConversation);
        $depthBefore = $this->stackDepth($simSession);

        return $this->applyProcedureSwitch($session, $simSession, $depthBefore, $handler->resumeCaller($simSession), false);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: ?string, 2: array<string, mixed>}
     */
    private function applyProcedureSwitch(array $session, SimulatedChatFlowSession $simSession, int $depthBefore, ?string $nextId, bool $isCall): array
    {
        $messages = [];
        $depthAfter = $this->stackDepth($simSession);
        $session['context'] = $simSession->context ?? [];

        if ($depthAfter > $depthBefore) {
            $session['call_stack'][] = ['nodes' => $session['nodes'], 'flow_id' => $session['flow_id'] ?? null];
            $session['nodes'] = $simSession->chatFlow->nodes ?? [];
            $session['flow_id'] = $simSession->chat_flow_id;
            $name = e((string) ($simSession->chatFlow->name ?? ''));
            $messages[] = ['type' => 'bot', 'text' => '📞 [Llamada al procedimiento'.($name !== '' ? " «{$name}»" : '').']', 'system' => true];

            return [$messages, $nextId, $session];
        }

        if ($depthAfter < $depthBefore) {
            $caller = null;

            for ($i = $depthBefore - $depthAfter; $i > 0; $i--) {
                $caller = array_pop($session['call_stack']);
            }

            $session['nodes'] = $caller['nodes'] ?? $session['nodes'];
            $session['flow_id'] = $caller['flow_id'] ?? null;
            $messages[] = ['type' => 'bot', 'text' => '↩️ [Fin del procedimiento: vuelve al flow que lo llamó]', 'system' => true];

            return [$messages, $nextId, $session];
        }

        if (($simSession->status ?? 'active') !== 'active') {
            $session['status'] = $simSession->status;
            $messages[] = ['type' => 'bot', 'text' => '🔀 [No se pudo llamar al procedimiento — transferido a agente]', 'system' => true];
        } elseif ($isCall) {
            $messages[] = ['type' => 'bot', 'text' => '⚠️ [No se pudo llamar al procedimiento (no existe, no está activo, llamada cíclica o demasiado profunda): se continúa]', 'system' => true];
        }

        return [$messages, $nextId, $session];
    }

    private function stackDepth(SimulatedChatFlowSession $simSession): int
    {
        $stack = $simSession->getContextValue(FlowCallNodeHandler::STACK_KEY, []);

        return is_array($stack) ? count($stack) : 0;
    }

    // ─── ai_action ─────────────────────────────────────────────────────────────

    /**
     * Runs the production ai_action handler on a test-mode executor: read
     * actions execute (source `test`), actions that modify data are simulated.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string, 2: array<string, mixed>}
     */
    private function simulateAiAction(array $session, array $node): array
    {
        $handler = $this->aiActionHandler ?? $this->testModeAiActionHandler();

        if ($handler === null) {
            $message = '⚠️ [Acción del catálogo IA no simulable: el módulo HelpdeskAiPrompts no está disponible]';
            $session['context'][trim((string) ($node['data']['save_to'] ?? '')) ?: 'accion'] = null;

            return [[['type' => 'bot', 'text' => $message, 'system' => true]], $this->firstChildId($session, $node['id']), $session];
        }

        [$messages, $nextId, $session] = $this->renderWithHandler($session, $node, $handler);

        $data = $node['data'] ?? [];
        $saveTo = trim((string) ($data['save_to'] ?? '')) ?: 'accion';
        $ok = (bool) ($session['context'][$saveTo.'_ok'] ?? false);
        $status = (string) ($session['context'][$saveTo.'_status'] ?? '');
        $key = e((string) ($data['action_key'] ?? ''));
        $var = e($saveTo);

        $summary = match (true) {
            $status === 'simulated' => "🧠 [Acción «{$key}» simulada: modifica datos y no se ejecuta en modo prueba → {{{$var}}}]",
            $ok => "🧠 [Acción «{$key}» ejecutada (solo lectura) → {{{$var}}}]",
            default => "🧠 [Acción «{$key}» falló ({$status})]",
        };

        array_unshift($messages, ['type' => 'bot', 'text' => $summary, 'system' => true]);

        return [$messages, $nextId, $session];
    }

    private function testModeAiActionHandler(): ?NodeHandler
    {
        if (! class_exists(AiActionNodeHandler::class)) {
            return null;
        }

        return new AiActionNodeHandler(
            new TestModeActionExecutor(app(ActionExecutor::class)),
            app(ChatFlowLocalizer::class),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items  Captured bot message payloads.
     * @return array<int, array<string, mixed>>
     */
    private function toPanelMessages(array $items): array
    {
        $messages = [];

        foreach ($items as $item) {
            $meta = $item['metadata'] ?? [];

            foreach ($meta['cards'] ?? [] as $card) {
                $messages[] = ['type' => 'bot', 'text' => '🖼️ '.e(trim(($card['title'] ?? '').' '.($card['image_url'] ?? ''))), 'system' => true];
            }
            if (! empty($meta['card']['image_url'])) {
                $messages[] = ['type' => 'bot', 'text' => '🖼️ '.e($meta['card']['image_url']), 'system' => true];
            }
            if (! empty($meta['attachment']['url'])) {
                $messages[] = ['type' => 'bot', 'text' => '📎 ['.e($meta['attachment']['type'] ?? 'document').': '.e($meta['attachment']['url']).']', 'system' => true];
            }

            // The panel inserts bot text as HTML: escape what the flow produced.
            $body = trim((string) ($item['body'] ?? ''));
            if ($body !== '') {
                $messages[] = ['type' => 'bot', 'text' => nl2br(e($body), false)];
            }
        }

        return $messages;
    }

    /**
     * Test-panel-only helpers shown under a waiting node's prompt.
     *
     * @return array<int, array<string, mixed>>
     */
    private function waitHints(array $session, array $node): array
    {
        if ($node['type'] === 'identify_customer') {
            return [['type' => 'bot', 'text' => '💡 Escribe <strong>test</strong> para simular identificación exitosa, o cualquier otro texto para simular fallo.', 'system' => true]];
        }

        if ($node['type'] === 'request_documents') {
            $uploaded = $session['context']['_doc_uploads_'.$node['id']] ?? [];
            $pending = array_values(array_diff($node['data']['doc_types'] ?? [], $uploaded));

            if ($pending !== []) {
                return [['type' => 'doc_upload_chips', 'chips' => array_map(fn ($t) => [
                    'key' => $t,
                    'label' => '📎 '.(config('helpdeskchatflow.document_labels', [])[$t] ?? $t),
                ], $pending)]];
            }
        }

        return [];
    }

    private function processInput(array $session, array $node, string $userMessage): array
    {
        $messages = [];
        $data = $node['data'] ?? [];
        $nextId = null;

        // Global escape to a human agent (a valid option selection wins).
        $optionMatch = $this->resolveNumberedChoice($userMessage, $data['options'] ?? []);
        if ($optionMatch === null && $this->isHumanEscapeRequest($userMessage)) {
            $session['status'] = 'transferred';
            $messages[] = ['type' => 'bot', 'text' => 'Te paso con un agente. Un momento, por favor. 🙋'];
            $messages[] = ['type' => 'bot', 'text' => '🔀 [Transferido a agente]', 'system' => true];

            return [$messages, $session];
        }

        switch ($node['type']) {
            case 'collect_input':
                $rule = $data['validation'] ?? 'none';
                if ($rule !== 'none' && ! $this->passesValidation($rule, $userMessage, $data)) {
                    $messages[] = ['type' => 'bot', 'text' => $this->validationError($rule, $data)];

                    return [$messages, $session]; // stay on the node
                }
                $varName = $data['variable_name'] ?? 'last_input';
                $session['context'][$varName] = $this->normalizeInput($rule, $userMessage, $data);
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'quick_replies':
            case 'rich_message':
                if ($optionMatch === null && ! empty($data['options'])) {
                    $options = array_values($data['options']);

                    // NLU: let the AI classify free-text replies when enabled.
                    if ($data['use_nlu'] ?? false) {
                        $idx = app(ChatFlowAiResponder::class)->classifyIntent($userMessage, $options);
                        if ($idx !== null) {
                            $optionMatch = $options[$idx - 1] ?? null;
                        }
                    }

                    if ($optionMatch === null) {
                        $messages[] = ['type' => 'bot', 'text' => "No reconocí esa opción. 🤔\n\n".$this->numberedList($options)."\n\nResponde con el número de la opción."];

                        return [$messages, $session]; // stay on the node
                    }
                }
                $resolved = $optionMatch ?? $userMessage;
                $varName = $data['variable_name'] ?? 'last_input';
                $session['context'][$varName] = $resolved;
                $matchChild = collect($session['nodes'])
                    ->first(fn ($n) => ($n['parentId'] ?? null) === $node['id']
                        && ($n['type'] ?? '') !== 'branchItem'
                        && ($n['label'] ?? '') === $resolved);
                $nextId = $matchChild['id'] ?? $this->firstChildId($session, $node['id']);
                break;

            case 'csat':
                $score = is_numeric(trim($userMessage)) ? (int) trim($userMessage) : $userMessage;
                $session['context']['csat_score'] = $score;
                $session['context'][$data['variable_name'] ?? 'csat_score'] = $score;
                if (! empty($data['thanks_message'])) {
                    $messages[] = ['type' => 'bot', 'text' => $this->interpolate($data['thanks_message'], $session['context'])];
                }
                $nextId = $this->firstChildId($session, $node['id']);
                break;

            case 'identify_customer':
                [$messages, $nextId, $session] = $this->processIdentification($session, $node, $data, $userMessage);
                break;

            case 'request_documents':
                [$messages, $nextId, $session] = $this->processDocumentUpload($session, $node, $data, $userMessage);
                break;

            default:
                $nextId = $this->firstChildId($session, $node['id']);
        }

        $stayTypes = ['identify_customer', 'request_documents'];

        if ($nextId === null && ! in_array($node['type'], $stayTypes) && $this->canResumeCaller($session)) {
            [$resumeMessages, $nextId, $session] = $this->resumeCaller($session);
            $messages = array_merge($messages, $resumeMessages);
        }

        if ($nextId) {
            [$nextMessages, $session] = $this->runFrom($session, $nextId);
            $messages = array_merge($messages, $nextMessages);
        } elseif ($session['status'] === 'active' && $nextId === null && ! in_array($node['type'], $stayTypes)) {
            $session['status'] = 'completed';
        }

        return [$messages, $session];
    }

    private function processIdentification(array $session, array $node, array $data, string $userMessage): array
    {
        $messages = [];
        $nextId = null;
        $attemptsKey = '_identify_attempts_'.$node['id'];
        $attempts = (int) ($session['context'][$attemptsKey] ?? 0) + 1;
        $maxAttempts = (int) ($data['max_attempts'] ?? 3);

        if (strtolower(trim($userMessage)) === 'test') {
            $session['context']['customer_identified'] = true;
            $session['context']['customer_name'] = 'Cliente Prueba';
            $session['context']['customer_email'] = 'test@example.com';

            $foundMsg = $this->interpolate(
                $data['found_message'] ?? '¡Perfecto, {{customer_name}}!',
                $session['context']
            );

            if ($foundMsg) {
                $messages[] = ['type' => 'bot', 'text' => $foundMsg];
            }

            $nextId = $this->firstChildId($session, $node['id']);

            return [$messages, $nextId, $session];
        }

        $session['context'][$attemptsKey] = $attempts;

        if ($attempts >= $maxAttempts) {
            $session['context']['customer_identified'] = false;
            $nextId = $this->getElseBranchNextId($session, $node['id']);

            if (! $nextId) {
                $session['status'] = 'transferred';
                $messages[] = ['type' => 'bot', 'text' => '🔀 [Agotados los intentos — transferido a agente]', 'system' => true];
            }

            return [$messages, $nextId, $session];
        }

        $notFoundMsg = $this->interpolate(
            $data['not_found_message'] ?? 'No encontramos ningún cliente con ese dato. Intenta con email, teléfono o documento.',
            $session['context']
        );

        $messages[] = ['type' => 'bot', 'text' => $notFoundMsg];

        // Stay on same node — return early without advancing
        return [$messages, null, $session];
    }

    private function processDocumentUpload(array $session, array $node, array $data, string $userMessage): array
    {
        $messages = [];
        $required = $data['doc_types'] ?? [];
        $uploadKey = '_doc_uploads_'.$node['id'];
        $uploaded = $session['context'][$uploadKey] ?? [];

        // Reverse-map label → key (strip "📎 " prefix if chip was clicked)
        $reverseLabels = [];
        foreach (config('helpdeskchatflow.document_labels', []) as $key => $label) {
            $reverseLabels['📎 '.$label] = $key;
            $reverseLabels[$label] = $key;
            $reverseLabels[$key] = $key;
        }

        $selectedKey = $reverseLabels[trim($userMessage)] ?? null;

        if ($selectedKey && in_array($selectedKey, $required) && ! in_array($selectedKey, $uploaded)) {
            $uploaded[] = $selectedKey;
            $session['context'][$uploadKey] = $uploaded;
            $messages[] = ['type' => 'bot', 'text' => '✅ '.(config('helpdeskchatflow.document_labels', [])[$selectedKey] ?? $selectedKey).' recibido.'];
        }

        $pending = array_values(array_diff($required, $uploaded));

        if (empty($pending)) {
            $varName = $data['variable_name'] ?? 'uploaded_docs';
            $session['context'][$varName] = $uploaded;
            $messages[] = ['type' => 'bot', 'text' => '📂 ¡Todos los documentos han sido enviados! Continuamos.'];
            $nextId = $this->firstChildId($session, $node['id']);
        } else {
            $chips = array_map(fn ($t) => [
                'key' => $t,
                'label' => '📎 '.(config('helpdeskchatflow.document_labels', [])[$t] ?? $t),
            ], $pending);
            $messages[] = ['type' => 'doc_upload_chips', 'chips' => array_values($chips)];
            $nextId = null;
        }

        return [$messages, $nextId, $session];
    }

    // ─── AI / Order / HTTP (test simulation) ────────────────────────────────────

    private function simulateAiResponse(array $session, array $node): array
    {
        $data = $node['data'] ?? [];
        $question = (string) ($session['context'][$data['question_variable'] ?? 'last_input'] ?? '');

        $result = app(ChatFlowAiResponder::class)->generate($question, $data, 'es');

        $messages = [['type' => 'bot', 'text' => $result['answer']]];

        if ($result['used_kb']) {
            $messages[] = ['type' => 'bot', 'text' => '📚 [Respuesta basada en el centro de ayuda]', 'system' => true];
        }

        if (! empty($data['save_to'])) {
            $session['context'][$data['save_to']] = $result['answer'];
        }

        return [$messages, $session];
    }

    private function simulateOrderLookup(array $session, array $node): array
    {
        $data = $node['data'] ?? [];
        $orderId = $session['context'][$data['order_variable'] ?? 'numero_pedido'] ?? null;

        $customer = [
            'erp_id' => $session['context']['customer_erp_id'] ?? null,
            'ps_id' => $session['context']['customer_ps_id'] ?? null,
            'email' => $session['context']['customer_email'] ?? null,
        ];

        $order = app(ChatFlowOrderLookup::class)->lookup($orderId, $customer, $data['source'] ?? 'auto');

        // In test mode, fall back to a demo order so designers can preview the layout.
        if (! $order['found'] && $orderId) {
            $order = [
                'found' => true,
                'order_id' => $orderId,
                'status' => 'En preparación',
                'date' => now()->subDays(2)->format('d/m/Y'),
                'total' => '49,90 €',
                'tracking' => 'ES'.str_pad((string) $orderId, 9, '0', STR_PAD_LEFT),
                'source' => 'demo',
                'raw' => [],
            ];
            $demo = true;
        } else {
            $demo = false;
        }

        $messages = [];

        if ($order['found']) {
            $session['context']['order_status'] = $order['status'] ?? '';
            $session['context']['order_total'] = $order['total'] ?? '';
            $session['context']['order_tracking'] = $order['tracking'] ?? '';

            $body = ! empty($data['found_message'])
                ? $this->interpolate($data['found_message'], $session['context'])
                : $this->demoOrderText($order);

            $messages[] = ['type' => 'bot', 'text' => $body];

            if ($demo) {
                $messages[] = ['type' => 'bot', 'text' => '🧪 [Pedido de ejemplo — en producción se consulta el ERP/PrestaShop real]', 'system' => true];
            }
        } else {
            $messages[] = ['type' => 'bot', 'text' => $data['not_found_message'] ?? 'No he encontrado ese pedido asociado a tu cuenta.'];
        }

        return [$messages, $session];
    }

    private function demoOrderText(array $order): string
    {
        $lines = ["📦 Pedido #{$order['order_id']}"];
        foreach (['status' => 'Estado', 'date' => 'Fecha', 'total' => 'Total', 'tracking' => 'Seguimiento'] as $key => $label) {
            if (! empty($order[$key])) {
                $lines[] = "{$label}: {$order[$key]}";
            }
        }

        return implode("\n", $lines);
    }

    private function simulateHttpRequest(array $session, array $node): array
    {
        $data = $node['data'] ?? [];
        $result = app(ChatFlowHttpRequester::class)->send($data, $session['context'] ?? []);

        $saveTo = $data['save_to'] ?? 'http_response';
        $session['context'][$saveTo] = $result['value'];
        $session['context'][$saveTo.'_ok'] = $result['ok'];
        $session['context'][$saveTo.'_status'] = $result['status'];

        $messages = [];

        if ($result['ok']) {
            $messages[] = ['type' => 'bot', 'text' => "🔌 [Petición HTTP OK ({$result['status']}) → guardado en {{$saveTo}}]", 'system' => true];
        } else {
            $messages[] = ['type' => 'bot', 'text' => '🔌 [Petición HTTP falló: '.($result['error'] ?? 'error').']', 'system' => true];
        }

        if (! empty($data['show_message']) && ! empty($data['message_template'])) {
            $messages[] = ['type' => 'bot', 'text' => $this->interpolate($data['message_template'], $session['context'])];
        }

        return [$messages, $session];
    }

    // ─── Branches ──────────────────────────────────────────────────────────────

    private function executeBranches(array $session, array $node): ?string
    {
        $items = collect($session['nodes'])
            ->filter(fn ($n) => ($n['parentId'] ?? null) === $node['id'] && ($n['type'] ?? '') === 'branchItem');
        $elseItem = null;

        foreach ($items as $item) {
            if ($item['data']['isElse'] ?? false) {
                $elseItem = $item;

                continue;
            }

            if ($this->evaluateConditions(
                $item['data']['conditions'] ?? [],
                strtolower((string) ($item['data']['match'] ?? 'all')),
                fn (string $variable): mixed => ContextPath::get($session['context'] ?? [], $variable),
            )) {
                return $this->firstChildId($session, $item['id']);
            }
        }

        return $elseItem ? $this->firstChildId($session, $elseItem['id']) : null;
    }

    private function getElseBranchNextId(array $session, string $nodeId): ?string
    {
        $nextId = $this->firstChildId($session, $nodeId);
        $nextNode = $nextId ? $this->getNode($session, $nextId) : null;

        if (! $nextNode || $nextNode['type'] !== 'branches') {
            return null;
        }

        $elseItem = collect($session['nodes'])
            ->first(fn ($n) => ($n['parentId'] ?? null) === $nextId
                && ($n['type'] ?? '') === 'branchItem'
                && ($n['data']['isElse'] ?? false));

        return $elseItem ? $this->firstChildId($session, $elseItem['id']) : null;
    }

    private function buildTerminalMessages(array $node): array
    {
        $messages = [];
        $data = $node['data'] ?? [];

        switch ($node['type']) {
            case 'transfer':
                $msg = trim($data['message'] ?? 'Un momento, te transfiero con un agente.');
                if ($msg) {
                    $messages[] = ['type' => 'bot', 'text' => $msg];
                }
                $team = $data['team'] ?? '';
                $label = $team ? "🔀 [Transferido a: {$team}]" : '🔀 [Transferido a agente]';
                $messages[] = ['type' => 'bot', 'text' => $label, 'system' => true];

                return [$messages, 'transferred'];

            case 'close':
                $farewell = trim($data['farewell'] ?? '');
                if ($farewell) {
                    $messages[] = ['type' => 'bot', 'text' => $farewell];
                }
                $messages[] = ['type' => 'bot', 'text' => '✅ [Conversación cerrada]', 'system' => true];

                return [$messages, 'completed'];

            case 'end':
            default:
                $farewell = trim($data['farewell'] ?? '');
                if ($farewell) {
                    $messages[] = ['type' => 'bot', 'text' => $farewell];
                }
                $label = match ($data['action'] ?? 'close') {
                    'close' => '✅ [Conversación cerrada]',
                    'transfer_to_agent' => '🔀 [Transferido a agente]',
                    default => '[Fin]',
                };
                $messages[] = ['type' => 'bot', 'text' => $label, 'system' => true];

                return [$messages, 'completed'];
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function getNode(array $session, ?string $id): ?array
    {
        if (! $id) {
            return null;
        }

        return collect($session['nodes'])->first(fn ($n) => ($n['id'] ?? '') === $id);
    }

    private function firstChildId(array $session, string $parentId): ?string
    {
        $child = collect($session['nodes'])
            ->first(fn ($n) => ($n['parentId'] ?? null) === $parentId
                && ($n['type'] ?? '') !== 'branchItem');

        return $child['id'] ?? null;
    }

    private function interpolate(string $text, array $context): string
    {
        return ContextPath::interpolate($text, $context);
    }
}
