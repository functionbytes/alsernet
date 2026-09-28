<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Helpdesk\Services\AI\PromptSanitizer;
use Modules\HelpdeskAiPrompts\Services\PromptComposer;
use Modules\HelpdeskAiPrompts\Services\PromptRunRecorder;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskPrestashop\Services\Ext\CatalogService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Services\PrestashopProductQueryService;

/**
 * An autonomous AI agent (function/tool calling): given the customer message and
 * context, the LLM decides which tool to call — look up an order, search the help
 * center, answer, or escalate — and we run it, feeding results back until the
 * agent answers or escalates. The "agentic" pattern competitors ship in 2026.
 *
 * OpenAI traffic goes through the core {@see AiClient} gateway, and untrusted
 * text (the customer message + KB chunks fed back as tool results) is fenced
 * with the shared {@see PromptSanitizer} before reaching the model (CFM-S4/S5).
 */
class ChatFlowAgentService
{
    private const MAX_STEPS = 6;

    /** Intentos de lookup_order sin sesión verificada (número + email) por sesión de flujo. */
    private const MAX_UNVERIFIED_ORDER_ATTEMPTS = 5;

    /** Productos por búsqueda que ve el modelo (y como máximo se muestran). */
    private const MAX_PRODUCTS = 6;

    private readonly ?AiClient $aiClient;

    private readonly ?PromptSanitizer $sanitizer;

    /**
     * @param  object|null  $embeddings  HelpdeskHelpcenter EmbeddingsService (optional)
     * @param  object|null  $insights  ChatFlowProductInsights (tallas/stock/plazos/comparativa; null sin HelpdeskPrestashop)
     */
    public function __construct(
        private readonly ChatFlowOrderLookup $orderLookup,
        private readonly ?object $embeddings = null,
        ?AiClient $aiClient = null,
        ?PromptSanitizer $sanitizer = null,
        private ?object $insights = null,
    ) {
        $this->aiClient = $aiClient ?? (class_exists(AiClient::class) ? new AiClient : null);
        $this->sanitizer = $sanitizer ?? (class_exists(PromptSanitizer::class) ? new PromptSanitizer : null);
    }

    /**
     * @param  array<string,mixed>  $context  Session context (customer_*, etc.)
     * @param  array<string,mixed>  $data  Node data (instructions, tools enabled)
     * @param  object|null  $catalog  Catálogo del canal (HelpdeskLivechat CatalogDriver:
     *                                search()/find()); activa product_search y product_detail
     * @param  object|null  $cart  Cesta del visitante (show(): ?array, add(int, int, int): array);
     *                             activa show_cart y add_to_cart
     * @param  array<int, array{role: string, content: string}>  $history  Prior conversation turns (memory)
     * @param  array{current_product: array<string,mixed>|null, cart: array<string,mixed>|null, viewed_products: array<int, array<string,mixed>>}|null  $visitorContext  Live widget-session snapshot (current product, cart, recently viewed)
     * @return array{action: string, text: string, used_tools: array<int,string>, products: array<int, object>}
     */
    public function run(
        string $question,
        array $context,
        array $data,
        string $locale = 'es',
        ?object $catalog = null,
        ?object $cart = null,
        array $history = [],
        ?array $visitorContext = null,
    ): array {
        $startedAt = microtime(true);
        $composed = $this->composePrompt($question, $context, $data, $locale, $visitorContext);

        // Casos que siempre pasan a una persona (p. ej. quejas): sin llamar al modelo.
        if ($composed !== null && ($composed['escalation'] ?? '') === 'always') {
            $result = [
                'action' => 'escalate',
                'text' => trim((string) ($composed['escalation_message'] ?? '')) ?: (string) ($data['fallback_message'] ?? 'Te paso con un agente.'),
                'used_tools' => [],
                'products' => [],
            ];
        } else {
            $result = $this->runAgent($question, $context, $data, $locale, $catalog, $cart, $history, $visitorContext, $composed);
        }

        $result['case'] = $composed['case_key'] ?? null;
        $this->recordRun($context, $composed, $result, $startedAt);

        return $result;
    }

    /**
     * Prompt de la librería (HelpdeskAiPrompts): base + caso detectado +
     * conocimiento. Solo si el nodo lo pide (use_prompt_library) y el módulo
     * está instalado; si no, se usan las instrucciones del nodo como siempre.
     *
     * @param  array<string,mixed>  $context
     * @param  array<string,mixed>  $data
     * @param  array<string,mixed>|null  $visitorContext
     * @return array<string,mixed>|null
     */
    private function composePrompt(string $question, array $context, array $data, string $locale, ?array $visitorContext): ?array
    {
        if (! ($data['use_prompt_library'] ?? false) || ! class_exists(PromptComposer::class)) {
            return null;
        }

        try {
            $composer = app(PromptComposer::class);
            $ctx = [
                'channel' => $data['_channel'] ?? ($visitorContext !== null ? 'web' : null),
                'locale' => strtolower(substr($locale, 0, 2)),
                'page_url' => $visitorContext['page_url'] ?? null,
                'logged_in' => $this->isVerifiedCustomer($context),
                'now' => now(),
            ];
            $composed = $composer->compose($question, $ctx, $data, $data['_forced_case'] ?? null, $data['_draft_case'] ?? null);

            // Respuesta a una pregunta del propio bot (ask_customer): sigue en el
            // mismo caso salvo que el cliente cambie claramente de tema (una
            // palabra clave de otro caso), p. ej. "¿y dónde está mi pedido?".
            $pending = $context['ai_pending_case'] ?? null;
            if (is_string($pending) && $pending !== '' && ! isset($data['_forced_case']) && ! isset($data['_draft_case'])
                && ($composed['case_key'] ?? null) !== $pending) {
                $options = array_map(fn ($o) => mb_strtolower(trim((string) $o)), (array) ($context['ai_pending_options'] ?? []));
                $pickedOption = in_array(mb_strtolower(trim($question)), $options, true);
                if ($pickedOption || ($composed['routed_by'] ?? '') !== 'keyword') {
                    $composed = $composer->compose($question, $ctx, $data, $pending);
                }
            }

            return $composed;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAgentService: prompt library failed, using node instructions', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Métricas por caso (resolución, derivación, herramientas) para el panel
     * de prompts. Las pruebas del panel (trace "test-*") no cuentan.
     *
     * @param  array<string,mixed>  $context
     * @param  array<string,mixed>|null  $composed
     * @param  array<string,mixed>  $result
     */
    private function recordRun(array $context, ?array $composed, array $result, float $startedAt): void
    {
        $traceId = (string) ($context['_trace_id'] ?? '');
        if ($composed === null || str_starts_with($traceId, 'test-') || ! class_exists(PromptRunRecorder::class)) {
            return;
        }

        try {
            app(PromptRunRecorder::class)->record(
                $traceId !== '' ? $traceId : null,
                $composed['case_key'] ?? null,
                (string) ($composed['routed_by'] ?? 'none'),
                (string) ($result['action'] ?? 'respond'),
                array_values(array_unique($result['used_tools'] ?? [])),
                (int) round((microtime(true) - $startedAt) * 1000),
            );
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAgentService: prompt run not recorded', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string,mixed>|null  $composed
     */
    private function runAgent(
        string $question,
        array $context,
        array $data,
        string $locale,
        ?object $catalog,
        ?object $cart,
        array $history,
        ?array $visitorContext,
        ?array $composed,
    ): array {
        $apiKey = config('services.openai.key', '');
        if (empty($apiKey) || trim($question) === '') {
            return ['action' => 'escalate', 'text' => $data['fallback_message'] ?? 'Te paso con un agente.', 'used_tools' => [], 'products' => []];
        }

        if (! ($data['tool_products'] ?? true) || ! $catalog || ! method_exists($catalog, 'search') || ! method_exists($catalog, 'find')) {
            $catalog = null;
        }
        if (! ($data['tool_cart'] ?? true) || ! $cart || $catalog === null || ! method_exists($cart, 'show') || ! method_exists($cart, 'add')) {
            $cart = null;
        }
        // Productos que el modelo consultó: se muestran al cliente como tarjetas.
        $shown = [];

        $tools = $this->buildTools($data, $catalog !== null, $cart !== null, $this->isVerifiedCustomer($context));
        // El caso limita las herramientas (p. ej. devoluciones no añade a la cesta);
        // responder y derivar están siempre disponibles.
        if ($composed !== null && is_array($composed['allowed_tools'] ?? null)) {
            $allowed = array_merge($composed['allowed_tools'], ['answer_customer', 'escalate_to_agent']);
            $tools = array_values(array_filter($tools, fn ($t) => in_array($t['function']['name'] ?? '', $allowed, true)));
        }
        $context['_locale'] = strtolower(substr($locale, 0, 2));
        $system = trim((string) ($composed['system'] ?? '')) ?: trim($data['instructions'] ?? 'Eres un agente de atención al cliente. Usa las herramientas disponibles cuando ayuden a resolver la consulta. Responde de forma breve y amable.');
        $lang = strtolower(substr($locale, 0, 2));
        if ($lang !== 'es') {
            $system .= " Responde SIEMPRE en el idioma del cliente (ISO: {$lang}).";
        }

        // Prompt-injection hardening: reaffirm the data/instructions boundary in
        // the system prompt and fence the untrusted customer text (CFM-S4).
        if ($this->sanitizer !== null) {
            $system .= ' '.$this->sanitizer->systemGuard();
        }

        $fallbackMessage = (string) ($data['fallback_message'] ?? 'Te paso con un agente.');

        $messages = [['role' => 'system', 'content' => $system]];

        // Fenced, refreshed on every step with the products shown so far so the
        // model doesn't re-suggest one it already offered.
        $visitorContextIndex = null;
        $visitorContextText = $this->buildVisitorContextText($visitorContext, $shown);
        if ($visitorContextText !== '') {
            $messages[] = ['role' => 'system', 'content' => $visitorContextText];
            $visitorContextIndex = count($messages) - 1;
        }

        foreach ($this->sanitizeHistory($history) as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => $this->wrapCustomerText($question)];
        $usedTools = [];

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $message = $this->callLlm($messages, $tools, $data);

            if ($message === null) {
                return ['action' => 'escalate', 'text' => $fallbackMessage, 'used_tools' => $usedTools, 'products' => []];
            }

            $toolCalls = $message['tool_calls'] ?? [];

            if (empty($toolCalls)) {
                $text = trim((string) ($message['content'] ?? ''));

                return $text !== ''
                    ? ['action' => 'respond', 'text' => $text, 'used_tools' => $usedTools, 'products' => $this->productsToShow($shown, $text)]
                    : ['action' => 'escalate', 'text' => $fallbackMessage, 'used_tools' => $usedTools, 'products' => []];
            }

            $messages[] = $message; // assistant turn with tool_calls

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $usedTools[] = $name;

                if ($name === 'answer_customer') {
                    // An empty answer would post a blank bubble to the customer —
                    // treat it the same as the model giving up.
                    $text = trim((string) ($args['text'] ?? ''));

                    return $text !== ''
                        ? ['action' => 'respond', 'text' => $text, 'used_tools' => $usedTools, 'products' => $this->productsToShow($shown, $text)]
                        : ['action' => 'escalate', 'text' => $fallbackMessage, 'used_tools' => $usedTools, 'products' => []];
                }
                if ($name === 'ask_customer') {
                    $asked = $this->buildQuestion($args);
                    if ($asked !== null) {
                        return ['action' => 'respond', 'text' => $asked['text'], 'used_tools' => $usedTools, 'products' => array_values($shown)]
                            + array_filter(['options' => $asked['options'], 'prompt' => $asked['prompt'], 'cards' => $asked['cards']]);
                    }
                }
                if ($name === 'escalate_to_agent') {
                    return ['action' => 'escalate', 'text' => trim((string) ($args['message'] ?? $fallbackMessage)) ?: $fallbackMessage, 'used_tools' => $usedTools, 'products' => []];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $this->executeTool($name, $args, $context, $catalog, $shown, $cart),
                ];
            }

            if ($visitorContextIndex !== null) {
                $messages[$visitorContextIndex]['content'] = $this->buildVisitorContextText($visitorContext, $shown);
            }
        }

        return ['action' => 'escalate', 'text' => 'Te paso con un agente para ayudarte mejor.', 'used_tools' => $usedTools, 'products' => []];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function sanitizeHistory(array $history, int $max = 8): array
    {
        $clean = [];

        foreach ($history as $turn) {
            $role = $turn['role'] ?? '';
            $content = trim((string) ($turn['content'] ?? ''));

            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $clean[] = ['role' => $role, 'content' => $content];
            }
        }

        return array_slice($clean, -$max);
    }

    /**
     * Compact, fenced summary of what the visitor is looking at right now —
     * current product, cart contents/total, recently viewed — plus the ids
     * already shown in this run so the model doesn't repeat them.
     *
     * @param  array{current_product: array<string,mixed>|null, cart: array<string,mixed>|null, viewed_products: array<int, array<string,mixed>>}|null  $visitorContext
     * @param  array<string, object>  $shown  Products already surfaced this run, keyed by id
     */
    private function buildVisitorContextText(?array $visitorContext, array $shown): string
    {
        if ($visitorContext === null) {
            return '';
        }

        $lines = [];

        $product = $visitorContext['current_product'] ?? null;
        if (is_array($product) && ! empty($product['id'])) {
            $lines[] = sprintf(
                'Viendo ahora: id=%s "%s" (%s)',
                $product['id'],
                $this->sanitize((string) ($product['title'] ?? '')),
                $this->formatMoney($product['price'] ?? null, $product['currency'] ?? null),
            );
        }

        $cart = $visitorContext['cart'] ?? null;
        if (is_array($cart) && ! empty($cart['lines'])) {
            $cartLines = collect($cart['lines'])
                ->take(10)
                ->map(fn ($l) => sprintf(
                    '- %s x%s (%s)',
                    $this->sanitize((string) ($l['name'] ?? ('producto '.($l['id_product'] ?? '')))),
                    $l['qty'] ?? 1,
                    $this->formatMoney($l['total'] ?? null, $cart['currency'] ?? null),
                ))
                ->implode("\n");
            $lines[] = sprintf("Cesta (total %s):\n%s", $this->formatMoney($cart['total'] ?? null, $cart['currency'] ?? null), $cartLines);
        }

        $viewed = array_slice($visitorContext['viewed_products'] ?? [], 0, 5);
        if ($viewed !== []) {
            $lines[] = 'Vistos recientemente: '.collect($viewed)
                ->map(fn ($v) => sprintf('id=%s "%s"', $v['id'] ?? '', $this->sanitize((string) ($v['title'] ?? ''))))
                ->implode(', ');
        }

        if ($shown !== []) {
            $lines[] = 'Ya mostrados en esta respuesta (no los repitas): '.implode(', ', array_keys($shown));
        }

        if ($lines === []) {
            return '';
        }

        $text = implode("\n\n", $lines);

        return $this->sanitizer?->wrap($text, 'CONTEXTO_VISITANTE') ?? $text;
    }

    private function formatMoney(mixed $amount, ?string $currency): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $formatted = number_format((float) $amount, 2);

        return $currency ? "{$formatted} {$currency}" : $formatted;
    }

    /**
     * @param  array<int, array<string,mixed>>  $messages
     * @param  array<int, array<string,mixed>>  $tools
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>|null
     */
    private function callLlm(array $messages, array $tools, array $data): ?array
    {
        return $this->aiClient?->chatCompletion($messages, [
            'model' => $data['model'] ?? config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
            'temperature' => 0.3,
            'tools' => $tools,
            'timeout' => 40,
            'retries' => 1,
            'retry_delay' => 400,
        ]);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<int, array<string,mixed>>
     */
    private function buildTools(array $data, bool $withProducts = false, bool $withCart = false, bool $verifiedCustomer = false): array
    {
        $fn = fn (string $name, string $desc, array $props, array $required = []) => [
            'type' => 'function',
            'function' => ['name' => $name, 'description' => $desc, 'parameters' => [
                // Sin parámetros: {} y no [] — OpenAI rechaza TODA la petición
                // (400 invalid_function_parameters) si properties es una lista.
                'type' => 'object', 'properties' => $props === [] ? new \stdClass : $props, 'required' => $required,
            ]],
        ];

        $tools = [
            $fn('answer_customer', 'Responde directamente al cliente con la información solicitada.',
                ['text' => ['type' => 'string', 'description' => 'La respuesta para el cliente']], ['text']),
            $fn('escalate_to_agent', 'Transfiere la conversación a un agente humano cuando no puedas resolverla.',
                ['message' => ['type' => 'string', 'description' => 'Mensaje de transición para el cliente']]),
            $fn('ask_customer', 'Haz UNA pregunta para concretar lo que necesita el cliente (uso, talla, presupuesto…) con opciones cortas que pulsará como botones, y opcionalmente enlaces a categorías de la tienda (de category_links). Úsala cuando la petición sea amplia ("busco botas") antes de recomendar; no la uses si ya tienes datos suficientes.',
                [
                    'question' => ['type' => 'string', 'description' => 'La pregunta, breve y amable'],
                    'options' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '2 a 6 respuestas cortas (1-4 palabras), p. ej. ["Caza", "Montaña", "Pesca", "Uso diario"]'],
                    'links' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                        'label' => ['type' => 'string'], 'url' => ['type' => 'string'],
                    ], 'required' => ['label', 'url']], 'description' => 'Hasta 3 enlaces "Ver …" a categorías (URL exacta de category_links)'],
                ], ['question', 'options']),
        ];

        if (($data['tool_order_lookup'] ?? true)) {
            $tools[] = $fn('lookup_order', $verifiedCustomer
                ? 'Consulta el estado de un pedido del cliente (ya identificado) por su número o referencia: estado, transportista, seguimiento y fechas.'
                : 'Consulta el estado de un pedido por su número o referencia Y el email con el que se hizo la compra (pídeselos al cliente; ambos obligatorios).',
                array_filter([
                    'order_id' => ['type' => 'string', 'description' => 'Número o referencia del pedido'],
                    'email' => $verifiedCustomer ? null : ['type' => 'string', 'description' => 'Email de la compra, tal como lo escribe el cliente'],
                ]), $verifiedCustomer ? ['order_id'] : ['order_id', 'email']);
            if ($verifiedCustomer) {
                $tools[] = $fn('list_my_orders', 'Lista los últimos pedidos del cliente identificado (número, fecha, estado, total) para cuando no sabe el número.', []);
            }
        }
        if (($data['tool_knowledge'] ?? true) && $this->embeddings !== null) {
            $tools[] = $fn('search_help', 'Busca información en el centro de ayuda para responder una pregunta.',
                ['query' => ['type' => 'string', 'description' => 'Lo que se quiere buscar']], ['query']);
        }
        if ($withProducts) {
            $tools[] = $fn('product_search', 'Busca productos con el buscador de la tienda. Traduce la necesidad del cliente a palabras clave + filtros (marca, categoría, precio, stock, orden). Los resultados se muestran al cliente como tarjetas con botón de añadir; en tu respuesta no repitas precios ni enlaces, resume y ayuda a elegir. Si la respuesta indica "relaxed", explica qué filtro no se pudo cumplir.',
                [
                    'query' => ['type' => 'string', 'description' => 'Palabras clave del producto (tipo, uso, material), sin frases completas ni la marca si va en brand'],
                    'brand' => ['type' => 'string', 'description' => 'Marca, si el cliente la pide'],
                    'category' => ['type' => 'string', 'description' => 'Categoría o deporte (caza, pesca, golf…), si ayuda a acotar'],
                    'price_min' => ['type' => 'number', 'description' => 'Precio mínimo en €'],
                    'price_max' => ['type' => 'number', 'description' => 'Precio máximo en € ("menos de 150" → 150)'],
                    'in_stock' => ['type' => 'boolean', 'description' => 'Solo con stock (true si lo necesita ya)'],
                    'sort' => ['type' => 'string', 'enum' => ['relevance', 'price_asc', 'price_desc', 'newest'], 'description' => 'Orden (relevance por defecto; price_asc si busca lo más barato)'],
                    'size' => ['type' => 'string', 'description' => 'Talla u opción que ha dicho el cliente ("43", "XL"): se comprueba su stock en cada producto'],
                ], ['query']);
            $tools[] = $fn('category_links', 'Busca categorías de la tienda (con su URL) para ofrecer enlaces "Ver botas de caza"… mientras concretas con el cliente.',
                ['query' => ['type' => 'string', 'description' => 'Tipo de producto o deporte, p. ej. "botas caza"']], ['query']);
            $tools[] = $fn('product_detail', 'Obtiene la ficha de un producto concreto del catálogo por su id (de product_search, o el que aparece como "Viendo ahora" en el contexto del visitante) para responder dudas sobre él.',
                ['product_id' => ['type' => 'string', 'description' => 'Id del producto. Omítelo para usar el producto que el visitante está viendo ahora mismo.']]);
            if ($this->insights() !== null && ($data['tool_variants'] ?? true)) {
                $tools[] = $fn('product_variants', 'Tallas/colores/variantes de un producto con su disponibilidad y plazo de entrega, y stock en tiendas físicas. Con "option" comprueba una concreta ("44", "talla XL", "marrón 42").',
                    [
                        'product_id' => ['type' => 'string', 'description' => 'Id del producto. Omítelo para el que está viendo ahora.'],
                        'option' => ['type' => 'string', 'description' => 'Opción que pregunta el cliente (opcional)'],
                    ]);
            }
            if ($this->insights() !== null && ($data['tool_compare'] ?? true)) {
                $tools[] = $fn('compare_products', 'Compara 2 o 3 productos (marca, precio, disponibilidad, plazo, variantes y descripción). Los ids salen de product_search o del contexto.',
                    ['product_ids' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Ids de 2 a 3 productos']], ['product_ids']);
            }
        }
        if ($withCart) {
            $tools[] = $fn('show_cart', 'Muestra lo que el cliente tiene ahora en su cesta de la tienda (productos, cantidades y total).', []);
            $tools[] = $fn('add_to_cart', 'Añade un producto a la cesta del cliente. Úsala SOLO si el cliente ha pedido o confirmado explícitamente en su último mensaje añadir ESE producto y cantidad; si no, pregúntale antes y no la llames.',
                [
                    'product_id' => ['type' => 'string', 'description' => 'Id del producto (de product_search, o el que está viendo ahora si no dio otro)'],
                    'quantity' => ['type' => 'integer', 'description' => 'Unidades (1 si no lo dice)'],
                    'option' => ['type' => 'string', 'description' => 'Talla/variante elegida por el cliente si el producto tiene opciones ("44", "XL", "marrón 42")'],
                    'customer_confirmed' => ['type' => 'boolean', 'description' => 'true solo si el cliente lo pidió/confirmó expresamente'],
                ], ['customer_confirmed']);
        }

        return $tools;
    }

    /**
     * Product id for product_detail/add_to_cart: the id argument if given,
     * otherwise the product the visitor is currently looking at (context
     * seeded by ChatFlowEngine::visitorContextSeed / the CONTEXTO_VISITANTE
     * block) — lets the model act on "this product" without having called
     * product_search first.
     *
     * @param  array<string,mixed>  $args
     * @param  array<string,mixed>  $context
     */
    private function resolveProductId(array $args, array $context): string
    {
        $id = trim((string) ($args['product_id'] ?? ''));

        return $id !== '' ? $id : trim((string) ($context['current_product_id'] ?? ''));
    }

    /**
     * @param  array<string,mixed>  $args
     * @param  array<string,mixed>  $context
     */
    private function executeTool(string $name, array $args, array $context, ?object $catalog = null, array &$shown = [], ?object $cart = null): string
    {
        try {
            if ($name === 'show_cart' && $cart !== null) {
                $snapshot = $cart->show();
                if (! is_array($snapshot) || empty($snapshot['lines'])) {
                    return 'La cesta del cliente está vacía.';
                }

                return json_encode([
                    'products_count' => $snapshot['products_count'] ?? null,
                    'total' => $snapshot['total'] ?? null,
                    'currency' => $snapshot['currency'] ?? null,
                    'lines' => array_map(fn ($l) => [
                        'product_id' => (string) ($l['id_product'] ?? ''),
                        'name' => $this->sanitize((string) ($l['name'] ?? '')),
                        'quantity' => $l['qty'] ?? null,
                        'total' => $l['total'] ?? null,
                    ], array_slice((array) $snapshot['lines'], 0, 20)),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'add_to_cart' && $cart !== null && $catalog !== null) {
                if (($args['customer_confirmed'] ?? false) !== true) {
                    return 'No añadido: primero pregunta al cliente si quiere añadir ese producto a su cesta.';
                }
                $product = $catalog->find($this->resolveProductId($args, $context));
                if ($product === null || ! $product->available) {
                    return 'No añadido: el producto no existe o no está disponible.';
                }
                $shown[(string) $product->id] = $product;
                $idProductAttribute = (int) ($product->idProductAttribute ?? 0);
                if ($product->hasCombinations ?? false) {
                    $wanted = trim((string) ($args['option'] ?? ''));
                    $option = ($wanted !== '' && $this->insights() !== null)
                        ? $this->insights()->optionFor((int) $product->id, $wanted, $context['_locale'] ?? null)
                        : null;
                    if ($option === null) {
                        return 'No añadido: el producto tiene opciones (talla, color...). Pregunta cuál quiere (consulta product_variants) o que la elija en la tarjeta.';
                    }
                    if (! ($option['available'] ?? false)) {
                        return 'No añadido: la opción "'.$this->sanitize((string) ($option['label'] ?? $wanted)).'" no tiene stock.';
                    }
                    $idProductAttribute = (int) $option['id_product_attribute'];
                    // La tarjeta sale ya con esa talla: "Añadir" directo, sin ficha.
                    $shown[(string) $product->id] = $this->withChosenOption($product, $option);
                }

                $result = $cart->add((int) $product->id, $idProductAttribute, max(1, (int) ($args['quantity'] ?? 1)));

                if ($result['ok'] ?? false) {
                    return 'Añadido a la cesta del cliente.';
                }

                // Visitante sin cesta todavía (la crea la tienda en su primer
                // "Añadir"): no es un error, la tarjeta ya lleva la opción elegida.
                return ($result['error'] ?? '') === 'no_cart'
                    ? 'No añadido todavía: el cliente aún no tiene cesta en la tienda. Dile que pulse "Añadir" en la tarjeta que le mostramos (ya lleva la opción elegida) y se añadirá al momento.'
                    : 'No se pudo añadir desde aquí; el cliente puede usar el botón Añadir de la tarjeta.';
            }

            if ($name === 'product_search' && $catalog !== null) {
                $query = trim((string) ($args['query'] ?? ''));
                $filters = array_filter([
                    'brand' => trim((string) ($args['brand'] ?? '')) ?: null,
                    'category' => trim((string) ($args['category'] ?? '')) ?: null,
                    'price_min' => is_numeric($args['price_min'] ?? null) ? (float) $args['price_min'] : null,
                    'price_max' => is_numeric($args['price_max'] ?? null) ? (float) $args['price_max'] : null,
                    'in_stock' => ($args['in_stock'] ?? false) === true ? true : null,
                    'sort' => in_array($args['sort'] ?? null, ['relevance', 'price_asc', 'price_desc', 'newest'], true) ? $args['sort'] : null,
                ], fn ($v) => $v !== null);

                // Buscador de la tienda con filtros (y relajación si no hay
                // resultados); catálogos sin esa capacidad usan la búsqueda simple.
                if (method_exists($catalog, 'searchWithFilters')) {
                    $found = $catalog->searchWithFilters($query, self::MAX_PRODUCTS, $filters);
                    $products = array_slice($found['products'] ?? [], 0, self::MAX_PRODUCTS);
                    $relaxed = $found['relaxed'] ?? [];
                } else {
                    $products = array_slice($catalog->search($query, self::MAX_PRODUCTS), 0, self::MAX_PRODUCTS);
                    $relaxed = [];
                }
                // Talla pedida: se comprueba en la tienda para cada resultado (el
                // modelo no puede inventarse "talla disponible") y, si hay stock,
                // la tarjeta sale ya con esa talla elegida.
                $size = trim((string) ($args['size'] ?? ''));
                $sizeStatus = [];
                foreach ($products as $i => $p) {
                    if ($size !== '' && $this->insights() !== null && ($p->hasCombinations ?? false)) {
                        $option = $this->insights()->optionFor((int) $p->id, $size, $context['_locale'] ?? null);
                        $sizeStatus[(string) $p->id] = $option === null
                            ? 'no existe esa talla'
                            : (($option['available'] ?? false) ? ($option['stock_level'] ?? 'disponible') : 'agotada');
                        if ($option !== null && ($option['available'] ?? false)) {
                            $products[$i] = $p = $this->withChosenOption($p, $option);
                        }
                    }
                    $shown[(string) $p->id] = $p;
                    $this->searchOnly[(string) $p->id] = true;
                }

                if ($products === []) {
                    return 'No hay productos en el catálogo que coincidan con esa búsqueda. Prueba con otras palabras clave o menos filtros.';
                }

                return json_encode(array_filter([
                    'relaxed' => $relaxed !== [] ? $relaxed : null,
                    'products' => array_map(fn ($p) => array_filter([
                        'id' => (string) $p->id,
                        'title' => $this->sanitize((string) $p->title),
                        'brand' => isset($p->brand) && $p->brand !== null ? $this->sanitize((string) $p->brand) : null,
                        'category' => isset($p->category) && $p->category !== null ? $this->sanitize((string) $p->category) : null,
                        'price' => $p->price,
                        'currency' => $p->currency,
                        'available' => $p->available,
                        'has_options' => (bool) ($p->hasCombinations ?? false),
                        'requested_size' => $sizeStatus[(string) $p->id] ?? null,
                    ], fn ($v) => $v !== null), $products),
                ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'product_detail' && $catalog !== null) {
                $product = $catalog->find($this->resolveProductId($args, $context));
                if ($product === null) {
                    return 'No se encontró ese producto en el catálogo.';
                }
                $shown[(string) $product->id] = $product;
                unset($this->searchOnly[(string) $product->id]);

                return json_encode([
                    'id' => (string) $product->id,
                    'title' => $this->sanitize((string) $product->title),
                    'description' => $this->sanitize((string) ($product->description ?? '')),
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'available' => $product->available,
                    'has_options' => (bool) ($product->hasCombinations ?? false),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'product_variants' && $catalog !== null && $this->insights() !== null) {
                $productId = (int) $this->resolveProductId($args, $context);
                if ($productId <= 0) {
                    return 'Indica de qué producto (id de product_search) o usa el que está viendo.';
                }
                $wanted = trim((string) ($args['option'] ?? ''));
                $variants = $this->insights()->variants($productId, $context['_locale'] ?? null);
                if ($variants === null) {
                    return 'No se encontró ese producto en el catálogo.';
                }
                if (($product = $catalog->find((string) $productId)) !== null) {
                    $shown[(string) $product->id] = $product;
                    unset($this->searchOnly[(string) $product->id]);
                }
                if ($wanted !== '') {
                    $asked = $this->insights()->optionFor($productId, $wanted, $context['_locale'] ?? null);
                    $variants['asked_option'] = $asked ?? 'No existe esa opción para este producto.';
                    // Con stock: la tarjeta que ve el cliente ya lleva esa talla.
                    if ($asked !== null && ($asked['available'] ?? false) && isset($product)) {
                        $shown[(string) $product->id] = $this->withChosenOption($product, $asked);
                    }
                }

                return json_encode($this->sanitizeDeep($variants), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'compare_products' && $catalog !== null && $this->insights() !== null) {
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($args['product_ids'] ?? [])))));
                if (count($ids) < 2) {
                    return 'Necesito al menos 2 productos para comparar (usa product_search para obtener sus ids).';
                }
                $comparison = $this->insights()->compare(array_slice($ids, 0, 3), $context['_locale'] ?? null);
                foreach (array_slice($ids, 0, 3) as $id) {
                    if (($product = $catalog->find((string) $id)) !== null) {
                        $shown[(string) $product->id] = $product;
                    }
                }

                return $comparison ? json_encode($this->sanitizeDeep($comparison), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'No se pudo comparar esos productos.';
            }

            if ($name === 'category_links') {
                $links = $this->findCategoryLinks(trim((string) ($args['query'] ?? '')), $context['_locale'] ?? null);

                return $links === [] ? 'No hay categorías que coincidan.' : json_encode($this->sanitizeDeep($links), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'list_my_orders') {
                if (! $this->isVerifiedCustomer($context)) {
                    return 'El cliente no está identificado: pídele el número de pedido y el email de la compra.';
                }
                $orders = $this->orderLookup->recentOrders($this->verifiedCustomer($context), 5);

                return $orders === [] ? 'El cliente no tiene pedidos en su cuenta.' : json_encode($this->sanitizeDeep($orders), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'lookup_order') {
                // Dos vías, las dos con propiedad comprobada:
                // - cliente verificado (OTP del flujo, o sesión de la tienda firmada
                //   por PrestaShop → identity_verified): sus datos del contexto;
                // - sin verificar: número/referencia + email de la compra (como el
                //   seguimiento de invitado de la tienda), con límite de intentos.
                // Nunca con customer_identified a secas (email escrito sin verificar).
                if ($this->isVerifiedCustomer($context)) {
                    $customer = $this->verifiedCustomer($context);
                } else {
                    $email = strtolower(trim((string) ($args['email'] ?? '')));
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return 'Para consultar el pedido necesito el número o referencia y el email con el que se hizo la compra.';
                    }
                    if (! $this->allowUnverifiedOrderAttempt($context)) {
                        return 'Demasiados intentos. Ofrece pasar con un agente para revisarlo.';
                    }
                    $customer = ['email' => $email];
                }

                $order = $this->orderLookup->lookup(trim((string) ($args['order_id'] ?? '')), $customer);

                if (! $order['found']) {
                    return 'No hay ningún pedido con ese número/referencia asociado a ese cliente o email.';
                }

                return json_encode($this->sanitizeDeep(array_filter([
                    'order_id' => $order['order_id'] ?? null,
                    'reference' => $order['reference'] ?? null,
                    'status' => $order['status'] ?? null,
                    'status_date' => $order['status_date'] ?? null,
                    'date' => $order['date'] ?? null,
                    'total' => $order['total'] ?? null,
                    'currency' => $order['currency'] ?? null,
                    'carrier' => $order['carrier'] ?? null,
                    'tracking_number' => $order['tracking_number'] ?? ($order['tracking'] ?? null),
                    'tracking_url' => $order['tracking_url'] ?? null,
                    'shipped_date' => $order['shipped_date'] ?? null,
                    'expected_date' => $order['expected_date'] ?? null,
                    'items' => $order['items'] ?? null,
                ], fn ($v) => $v !== null && $v !== '' && $v !== [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($name === 'search_help' && $this->embeddings !== null) {
                $results = $this->embeddings->search($args['query'] ?? '', 3);
                $chunks = collect($results)
                    ->map(fn ($r) => $this->sanitize((string) ($r['chunk_text'] ?? '')))
                    ->filter()
                    ->take(3)
                    ->implode("\n---\n");

                return $chunks !== '' ? $chunks : 'No se encontró información relevante en el centro de ayuda.';
            }
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAgentService: tool failed', ['tool' => $name, 'error' => $e->getMessage()]);

            return 'La herramienta no está disponible en este momento.';
        }

        return 'Herramienta desconocida.';
    }

    /**
     * Pregunta con botones (ask_customer): opciones cortas y, como tarjetas,
     * enlaces "Ver …" SOLO a URLs de la tienda (nunca enlaces que invente el
     * modelo a otros dominios). El cuerpo lleva la lista numerada para los
     * canales sin botones (WhatsApp, email); el widget muestra prompt + botones.
     *
     * @param  array<string,mixed>  $args
     * @return array{text: string, prompt: string, options: array<int,string>, cards: array<int,array<string,string>>}|null
     */
    private function buildQuestion(array $args): ?array
    {
        $question = trim((string) ($args['question'] ?? ''));
        $options = array_values(array_unique(array_filter(array_map(
            fn ($o) => mb_substr(trim((string) $o), 0, 40),
            is_array($args['options'] ?? null) ? $args['options'] : [],
        ))));
        $options = array_slice($options, 0, 6);

        if ($question === '' || count($options) < 2) {
            return null;
        }

        $storeHost = parse_url((string) (config('helpdeskchatflow.store_url') ?: ''), PHP_URL_HOST);
        $cards = [];
        foreach (array_slice(is_array($args['links'] ?? null) ? $args['links'] : [], 0, 3) as $link) {
            $url = trim((string) ($link['url'] ?? ''));
            $label = mb_substr(trim((string) ($link['label'] ?? '')), 0, 60);
            if ($label === '' || ! in_array($url, $this->knownCategoryUrls, true) && ! ($storeHost && parse_url($url, PHP_URL_HOST) === $storeHost)) {
                continue;
            }
            $cards[] = ['title' => $label, 'subtitle' => '', 'image_url' => null, 'url' => $url];
        }

        $numbered = implode("\n", array_map(fn ($i, $o) => ($i + 1).'. '.$o, array_keys($options), $options));

        return ['text' => $question."\n\n".$numbered, 'prompt' => $question, 'options' => $options, 'cards' => $cards];
    }

    /** @var array<string, bool> Productos que solo salieron en una búsqueda (sin ficha/tallas consultadas). */
    private array $searchOnly = [];

    /**
     * Tarjetas a mostrar: las que el modelo consultó en detalle y, de las de
     * búsqueda, solo las que menciona en su respuesta (así no salen resultados
     * que descartó, p. ej. un bastón al pedir botas). Si no nombra ninguna, todas.
     *
     * @param  array<string, object>  $shown
     * @return array<int, object>
     */
    private function productsToShow(array $shown, string $text): array
    {
        $normalize = fn (string $t) => mb_strtolower(Str::ascii($t));
        $haystack = $normalize($text);

        $mentioned = array_filter($shown, function ($p, $id) use ($haystack, $normalize) {
            if (! isset($this->searchOnly[(string) $id])) {
                return true;
            }
            // Título base (sin " · Talla: 43") y primeras palabras significativas.
            $title = $normalize(explode(' · ', (string) ($p->title ?? ''))[0]);
            $words = array_slice(array_values(array_filter(preg_split('/\s+/', $title) ?: [], fn ($w) => mb_strlen($w) > 3)), 0, 3);

            return $words !== [] && count(array_filter($words, fn ($w) => str_contains($haystack, $w))) >= min(2, count($words));
        }, ARRAY_FILTER_USE_BOTH);

        $this->searchOnly = [];

        return array_values($mentioned !== [] ? $mentioned : $shown);
    }

    /** @var array<int, string> URLs de categoría devueltas por category_links en esta ejecución. */
    private array $knownCategoryUrls = [];

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function findCategoryLinks(string $query, ?string $lang): array
    {
        if ($query === '' || ! class_exists(PrestashopContextService::class)) {
            return [];
        }

        $normalize = fn (string $t) => mb_strtolower(Str::ascii($t));
        $words = array_values(array_filter(preg_split('/\s+/', $normalize($query)) ?: [], fn ($w) => mb_strlen($w) >= 3));
        if ($words === []) {
            return [];
        }
        // Sinónimos de cómo se llaman las categorías en la tienda ("Calzado",
        // "Ropa"…): con solo "botas" no salían las de caza o montaña.
        $synonyms = [
            'botas' => ['calzado'], 'bota' => ['calzado'], 'zapatillas' => ['calzado'], 'zapatos' => ['calzado'],
            'chaqueta' => ['ropa', 'chaquetas'], 'chaquetas' => ['ropa'], 'pantalon' => ['ropa', 'pantalones'],
            'pantalones' => ['ropa'], 'camiseta' => ['ropa'], 'chaleco' => ['ropa', 'chalecos'],
        ];
        foreach ($words as $w) {
            foreach ($synonyms[$w] ?? [] as $extra) {
                $words[] = $extra;
            }
        }
        $words = array_values(array_unique($words));

        $scored = [];
        foreach (app(PrestashopContextService::class)->getCategories($lang) as $cat) {
            if (empty($cat['url']) || empty($cat['name'])) {
                continue;
            }
            $haystack = $normalize($cat['name'].' '.($cat['parent'] ?? ''));
            // Coincidencia por palabra con raíz (botas ~ bota): prefijo de 4 letras.
            $score = 0;
            foreach ($words as $w) {
                if (str_contains($haystack, mb_substr($w, 0, max(4, mb_strlen($w) - 1)))) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = [$score, $cat];
            }
        }
        usort($scored, fn ($a, $b) => $b[0] <=> $a[0]);

        // Variedad: primero una categoría por sección padre (Caza, Pesca,
        // Esquí…) para que el modelo elija las que encajan con sus opciones;
        // con empate de puntuación salían solo "botas de esquí".
        $picked = [];
        $parents = [];
        foreach ($scored as [$score, $cat]) {
            $parent = mb_strtolower((string) ($cat['parent'] ?? ''));
            if (! isset($parents[$parent])) {
                $parents[$parent] = true;
                $picked[] = [$score, $cat];
            }
        }
        foreach ($scored as $entry) {
            if (count($picked) >= 6) {
                break;
            }
            if (! in_array($entry, $picked, true)) {
                $picked[] = $entry;
            }
        }

        $links = [];
        foreach (array_slice($picked, 0, 6) as [, $cat]) {
            $name = $cat['parent'] ? $cat['name'].' ('.$cat['parent'].')' : $cat['name'];
            $links[] = ['name' => $name, 'url' => (string) $cat['url']];
            $this->knownCategoryUrls[] = (string) $cat['url'];
        }

        return $links;
    }

    /**
     * Copia del producto con la combinación elegida ("Talla: 42") para que la
     * tarjeta del widget la añada directamente en vez de pedir "Elegir opciones".
     * El precio lo sigue calculando la tienda al añadir.
     *
     * @param  array<string, mixed>  $option
     */
    private function withChosenOption(object $product, array $option): object
    {
        if (! $product instanceof CatalogProduct || empty($option['id_product_attribute'])) {
            return $product;
        }

        $label = trim((string) ($option['label'] ?? ''));

        return CatalogProduct::fromArray(array_merge($product->toArray(), [
            'id_product_attribute' => (int) $option['id_product_attribute'],
            'has_combinations' => false,
            'title' => $label !== '' ? $product->title.' · '.$label : $product->title,
        ]));
    }

    /**
     * Tallas/stock/plazos (ChatFlowProductInsights). Si no se inyectó (provider
     * de ChatFlow sin registrar) se resuelve aquí, igual que el catálogo.
     */
    private function insights(): ?object
    {
        if ($this->insights === null && class_exists(ChatFlowProductInsights::class) && class_exists(PrestashopProductQueryService::class)) {
            $this->insights = new ChatFlowProductInsights(
                class_exists(CatalogService::class) ? app(CatalogService::class) : null,
                app(PrestashopProductQueryService::class),
            );
        }

        return $this->insights;
    }

    /**
     * Cliente con identidad comprobada: OTP del flujo o sesión de la tienda
     * firmada por PrestaShop (identity_verified, sembrado por ChatFlowEngine).
     *
     * @param  array<string,mixed>  $context
     */
    private function isVerifiedCustomer(array $context): bool
    {
        return ! empty($context['customer_identified_via_otp']) || ! empty($context['identity_verified']);
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array{erp_id: mixed, ps_id: mixed, email: mixed}
     */
    private function verifiedCustomer(array $context): array
    {
        return [
            'erp_id' => $context['customer_erp_id'] ?? null,
            'ps_id' => $context['customer_ps_id'] ?? null,
            'email' => $context['customer_email'] ?? null,
        ];
    }

    /**
     * Límite de consultas número+email sin verificar por sesión del flujo
     * (evita probar combinaciones). La clave es el trace id de la sesión.
     *
     * @param  array<string,mixed>  $context
     */
    private function allowUnverifiedOrderAttempt(array $context): bool
    {
        $key = 'chatflow:order-attempts:'.($context['_trace_id'] ?? 'none');
        $attempts = (int) Cache::get($key, 0);
        if ($attempts >= self::MAX_UNVERIFIED_ORDER_ATTEMPTS) {
            return false;
        }
        Cache::put($key, $attempts + 1, now()->addHour());

        return true;
    }

    /**
     * Sanea recursivamente los textos que vienen de la tienda antes de dárselos
     * al modelo (nombres de producto o estados podrían traer instrucciones).
     */
    private function sanitizeDeep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->sanitizeDeep($v), $value);
        }

        return is_string($value) ? $this->sanitize($value) : $value;
    }

    /**
     * Fence untrusted customer text inside a clearly labelled data block so the
     * model treats it as data, never as instructions. Falls back to the raw text
     * when the shared sanitizer is unavailable.
     */
    private function wrapCustomerText(string $text): string
    {
        return $this->sanitizer?->wrap($text, 'MENSAJE_CLIENTE') ?? $text;
    }

    /**
     * Neutralize untrusted KB text fed back to the model as a tool result.
     */
    private function sanitize(string $text): string
    {
        return $this->sanitizer?->sanitize($text) ?? $text;
    }
}
