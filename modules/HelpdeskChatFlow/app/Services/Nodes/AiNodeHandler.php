<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\ResolvesVisitorContext;
use Modules\HelpdeskChatFlow\Services\HandoffContextNote;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;
use Modules\HelpdeskLivechat\Events\BotTyping;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\NullCatalogDriver;
use Modules\HelpdeskLivechat\Services\Commerce\WidgetCartGateway;
use Modules\HelpdeskLivechat\Services\Widget\ProductShowcaseService;

/**
 * AI nodes: `ai_response` (RAG answer) and `ai_agent` (tool-calling agent with
 * orders, help center, catalog and cart tools).
 */
class AiNodeHandler implements NodeHandler
{
    use PostsBotMessages, RendersNodeMessages, ResolvesVisitorContext;

    public const TYPES = ['ai_response', 'ai_agent'];

    public function __construct(
        private readonly ChatFlowAiResponder $aiResponder,
        private readonly ChatFlowAgentService $agent,
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'ai_response' => $this->executeAiResponse($node, $session, $conversation),
            'ai_agent' => $this->executeAiAgent($node, $session, $conversation),
        };
    }

    private function executeAiResponse(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $questionVar = $data['question_variable'] ?? 'last_input';
        $question = (string) ($session->getContextValue($questionVar) ?? '');
        // Customer language drives the AI's reply language (LLM-native, no translation layer).
        $locale = (string) ($session->getContextValue('customer_lang')
            ?? $conversation->locale
            ?? config('app.locale', 'es'));

        $history = ($data['use_memory'] ?? true)
            ? $this->conversationHistory($conversation)
            : [];

        $result = $this->aiResponder->generate($question, $data, $locale, $history);

        $this->postBotMessage($conversation, $node['id'], $result['answer'], [
            'ai_generated' => true,
            'ai_used_kb' => $result['used_kb'],
            'ai_sources' => $result['sources'],
        ]);

        $values = ['ai_used_kb' => $result['used_kb']];
        if (! empty($data['save_to'])) {
            $values[$data['save_to']] = $result['answer'];
        }
        $session->setContextValues($values);

        return $this->getFirstChildId($node, $session);
    }

    private function broadcastBotTyping(Conversation $conversation, bool $typing): void
    {
        if (! class_exists(BotTyping::class)) {
            return;
        }

        try {
            broadcast(new BotTyping($conversation, $typing));
        } catch (\Throwable) {
            // Reverb caído: solo se pierde el indicador.
        }
    }

    private function executeAiAgent(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $question = (string) ($session->getContextValue($data['question_variable'] ?? 'last_input') ?? '');
        $locale = (string) ($session->getContextValue('customer_lang') ?? $conversation->locale ?? config('app.locale', 'es'));

        // Vuelta de un procedimiento: la misma pregunta y el mismo caso, ahora
        // con los datos recogidos (la respuesta del cliente al procedimiento
        // sobrescribió last_input).
        $procedureDone = (array) ($session->getContextValue('_ai_procedure_done') ?? []);
        if ($procedureDone !== []) {
            $question = (string) ($session->getContextValue('_ai_procedure_question') ?? $question);
            $data['_forced_case'] ??= $procedureDone[0];
        }

        $catalog = $this->catalogFor($conversation);
        $history = ($data['use_memory'] ?? true) ? $this->conversationHistory($conversation) : [];
        $visitorContext = $this->resolveVisitorContext($conversation);
        // "Escribiendo…" en el widget mientras la IA piensa (3-10 s con herramientas).
        $this->broadcastBotTyping($conversation, true);

        try {
            $result = $this->agent->run($question, $session->context ?? [], $data, $locale, $catalog, $this->cartFor($conversation), $history, $visitorContext);
        } finally {
            $this->broadcastBotTyping($conversation, false);
        }

        if ($result['action'] === 'procedure') {
            $procedureNodeId = $this->enterProcedure($node, $session, $result, $question);

            if ($procedureNodeId !== null) {
                return $procedureNodeId;
            }

            // Procedimiento no disponible: responde la IA como siempre.
            $result = $this->runWithoutProcedure((string) $result['case'], $question, $session, $data, $locale, $catalog, $conversation, $history, $visitorContext);
        }

        // Pregunta con botones/enlaces (ask_customer): el widget pinta prompt +
        // opciones pulsables y tarjetas "Ver …"; otros canales, la lista numerada.
        $this->postBotMessage($conversation, $node['id'], $result['text'], array_filter([
            'ai_agent' => true,
            'used_tools' => $result['used_tools'],
            'bot_options' => $result['options'] ?? null,
            'bot_prompt' => isset($result['options']) ? ($result['prompt'] ?? null) : null,
            'cards' => ! empty($result['cards']) ? $result['cards'] : null,
        ], fn ($v) => $v !== null));

        // Productos que consultó el agente IA → tarjetas con "Añadir al carrito"
        // en el widget (mismo carrusel que envía un agente humano).
        if (! empty($result['products']) && class_exists(ProductShowcaseService::class)) {
            try {
                app(ProductShowcaseService::class)
                    ->showcase($conversation, $result['products'], null, null, true);
            } catch (\Throwable $e) {
                Log::warning('ChatFlow ai_agent: product showcase failed', ['conversation_id' => $conversation->id, 'error' => $e->getMessage()]);
            }
        }

        // Si el bot acaba de preguntar con opciones, la respuesta del cliente
        // sigue en el mismo caso de prompt (ver ChatFlowAgentService).
        $asked = ! empty($result['options']);
        $session->setContextValues([
            'ai_pending_case' => $asked ? ($result['case'] ?? null) : null,
            'ai_pending_options' => $asked ? $result['options'] : null,
            ...($asked ? [] : $this->clearedProcedureState()),
        ]);

        if ($result['action'] === 'escalate') {
            app(HandoffContextNote::class)->post(
                $conversation,
                $session,
                HandoffContextNote::REASON_AI_ESCALATION,
                ($session->flowConditions()['handoff_summary'] ?? false)
                    ? fn (): ?string => app(ChatFlowHandoffSummary::class)->generate($conversation)
                    : null,
            );
            $conversation->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);

            return null;
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Entra en el procedimiento del caso (return = este mismo nodo ai_agent) y
     * devuelve su nodo de inicio, o null si no se puede (no existe, no es un
     * procedimiento activo, ciclo, profundidad). Se usa el mismo mecanismo que
     * call_flow (no ChatFlowEngine::callProcedure): ese ejecuta el procedimiento
     * anidado dentro del bucle del motor, y si se pausa en un collect_input el
     * bucle exterior cerraría la llamada.
     *
     * @param  array<string,mixed>  $result
     */
    private function enterProcedure(array $node, ChatFlowSession $session, array $result, string $question): ?string
    {
        $context = $session->context ?? [];

        try {
            $calls = app(FlowCallNodeHandler::class);
            $procedure = $calls->load((int) ($result['procedure_flow_id'] ?? 0));

            if ($procedure === null) {
                throw new FlowCallRefused('El procedimiento del caso no existe.');
            }

            $input = [];
            foreach ((array) ($result['input'] ?? []) as $name => $template) {
                $input[$name] = is_string($template) ? ContextPath::interpolate($template, $context) : $template;
            }

            // Marca antes de entrar: al volver al nodo no se vuelve a llamar.
            $session->setContextValues([
                '_ai_procedure_done' => [$result['case']],
                '_ai_procedure_question' => $question,
                '_ai_procedure_keys' => array_keys($context),
            ]);

            return $calls->enter($session, $procedure, $node['id'], $input, (array) ($result['outputs'] ?? []));
        } catch (FlowCallRefused $e) {
            Log::warning('ChatFlow ai_agent: procedure refused, answering with AI', [
                'session_id' => $session->id,
                'node_id' => $node['id'],
                'case' => $result['case'] ?? null,
                'reason' => $e->getMessage(),
            ]);

            $session->setContextValues($this->clearedProcedureState());

            return null;
        }
    }

    /**
     * La IA responde sin procedimiento: se marca el caso como hecho para que el
     * agente no devuelva otra vez la acción 'procedure'.
     *
     * @param  array<string,mixed>  $data
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<string,mixed>
     */
    private function runWithoutProcedure(string $caseKey, string $question, ChatFlowSession $session, array $data, string $locale, ?object $catalog, Conversation $conversation, array $history, ?array $visitorContext): array
    {
        $context = $session->context ?? [];
        $context['_ai_procedure_done'] = [$caseKey];

        return $this->agent->run($question, $context, $data, $locale, $catalog, $this->cartFor($conversation), $history, $visitorContext);
    }

    /**
     * @return array<string, null>
     */
    private function clearedProcedureState(): array
    {
        return ['_ai_procedure_done' => null, '_ai_procedure_question' => null, '_ai_procedure_keys' => null];
    }

    /**
     * Catálogo del canal web de la conversación (HelpdeskLivechat), o null si
     * el módulo no está o el canal no tiene catálogo. Sin él, el agente IA no
     * ofrece las herramientas de producto.
     */
    private function catalogFor(Conversation $conversation): ?object
    {
        if (! class_exists(CatalogManager::class)) {
            return null;
        }

        $channel = $conversation->inbox?->channel;
        if (! $channel instanceof Web) {
            return null;
        }

        $driver = app(CatalogManager::class)->forWeb($channel, $conversation->customer?->language);

        return $driver instanceof NullCatalogDriver ? null : $driver;
    }

    /**
     * Cesta del visitante (HelpdeskLivechat WidgetCartGateway) para las
     * herramientas show_cart/add_to_cart del agente IA, o null sin módulo.
     */
    private function cartFor(Conversation $conversation): ?object
    {
        if (! class_exists(WidgetCartGateway::class)) {
            return null;
        }

        return new class(app(WidgetCartGateway::class), $conversation)
        {
            public function __construct(private readonly object $gateway, private readonly Conversation $conversation) {}

            public function show(): ?array
            {
                return $this->gateway->snapshot($this->conversation);
            }

            public function add(int $productId, int $attributeId, int $quantity): array
            {
                return $this->gateway->addProduct($this->conversation, $productId, $attributeId, $quantity);
            }
        };
    }

    /**
     * Recent conversation turns for AI memory, oldest first.
     *
     * Fetches one extra row and drops the most recent one: that last item is
     * the customer message that triggered this very node run (already sent
     * separately as the current question), so including it here duplicated it
     * in the prompt. Role is 'user' only for genuine customer messages —
     * everything else (bot AND human agent replies) is 'assistant', since a
     * human-agent turn labelled 'user' made the model think the customer had
     * said it.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function conversationHistory(Conversation $conversation, int $limit = 8): array
    {
        return $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest('id')
            ->limit($limit + 1)
            ->get()
            ->reverse()
            ->values()
            ->slice(0, -1)
            ->map(fn ($item) => [
                'role' => $item->isFromCustomer() ? 'user' : 'assistant',
                'content' => (string) ($item->body ?? ''),
            ])
            ->values()
            ->all();
    }
}
