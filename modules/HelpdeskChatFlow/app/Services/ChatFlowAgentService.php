<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Helpdesk\Services\AI\PromptSanitizer;

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
    private const MAX_STEPS = 4;

    /** Productos por búsqueda que ve el modelo (y como máximo se muestran). */
    private const MAX_PRODUCTS = 6;

    private readonly ?AiClient $aiClient;

    private readonly ?PromptSanitizer $sanitizer;

    /**
     * @param  object|null  $embeddings  HelpdeskHelpcenter EmbeddingsService (optional)
     */
    public function __construct(
        private readonly ChatFlowOrderLookup $orderLookup,
        private readonly ?object $embeddings = null,
        ?AiClient $aiClient = null,
        ?PromptSanitizer $sanitizer = null,
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
     * @return array{action: string, text: string, used_tools: array<int,string>, products: array<int, object>}
     */
    public function run(string $question, array $context, array $data, string $locale = 'es', ?object $catalog = null, ?object $cart = null): array
    {
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

        $tools = $this->buildTools($data, $catalog !== null, $cart !== null);
        $system = trim($data['instructions'] ?? 'Eres un agente de atención al cliente. Usa las herramientas disponibles cuando ayuden a resolver la consulta. Responde de forma breve y amable.');
        $lang = strtolower(substr($locale, 0, 2));
        if ($lang !== 'es') {
            $system .= " Responde SIEMPRE en el idioma del cliente (ISO: {$lang}).";
        }

        // Prompt-injection hardening: reaffirm the data/instructions boundary in
        // the system prompt and fence the untrusted customer text (CFM-S4).
        if ($this->sanitizer !== null) {
            $system .= ' '.$this->sanitizer->systemGuard();
        }

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $this->wrapCustomerText($question)],
        ];
        $usedTools = [];

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $message = $this->callLlm($messages, $tools, $data);

            if ($message === null) {
                return ['action' => 'escalate', 'text' => $data['fallback_message'] ?? 'Te paso con un agente.', 'used_tools' => $usedTools, 'products' => []];
            }

            $toolCalls = $message['tool_calls'] ?? [];

            if (empty($toolCalls)) {
                return ['action' => 'respond', 'text' => trim((string) ($message['content'] ?? '')), 'used_tools' => $usedTools, 'products' => array_values($shown)];
            }

            $messages[] = $message; // assistant turn with tool_calls

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $usedTools[] = $name;

                if ($name === 'answer_customer') {
                    return ['action' => 'respond', 'text' => trim((string) ($args['text'] ?? '')), 'used_tools' => $usedTools, 'products' => array_values($shown)];
                }
                if ($name === 'escalate_to_agent') {
                    return ['action' => 'escalate', 'text' => trim((string) ($args['message'] ?? 'Te paso con un agente.')), 'used_tools' => $usedTools, 'products' => []];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $this->executeTool($name, $args, $context, $catalog, $shown, $cart),
                ];
            }
        }

        return ['action' => 'escalate', 'text' => 'Te paso con un agente para ayudarte mejor.', 'used_tools' => $usedTools, 'products' => []];
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
    private function buildTools(array $data, bool $withProducts = false, bool $withCart = false): array
    {
        $fn = fn (string $name, string $desc, array $props, array $required = []) => [
            'type' => 'function',
            'function' => ['name' => $name, 'description' => $desc, 'parameters' => [
                'type' => 'object', 'properties' => $props, 'required' => $required,
            ]],
        ];

        $tools = [
            $fn('answer_customer', 'Responde directamente al cliente con la información solicitada.',
                ['text' => ['type' => 'string', 'description' => 'La respuesta para el cliente']], ['text']),
            $fn('escalate_to_agent', 'Transfiere la conversación a un agente humano cuando no puedas resolverla.',
                ['message' => ['type' => 'string', 'description' => 'Mensaje de transición para el cliente']]),
        ];

        if (($data['tool_order_lookup'] ?? true)) {
            $tools[] = $fn('lookup_order', 'Consulta el estado de un pedido del cliente identificado por su número.',
                ['order_id' => ['type' => 'string', 'description' => 'Número de pedido']], ['order_id']);
        }
        if (($data['tool_knowledge'] ?? true) && $this->embeddings !== null) {
            $tools[] = $fn('search_help', 'Busca información en el centro de ayuda para responder una pregunta.',
                ['query' => ['type' => 'string', 'description' => 'Lo que se quiere buscar']], ['query']);
        }
        if ($withProducts) {
            $tools[] = $fn('product_search', 'Busca productos en el catálogo de la tienda. Los resultados se muestran al cliente como tarjetas con botón de añadir al carrito; en tu respuesta no repitas precios ni enlaces, resume y ayuda a elegir.',
                ['query' => ['type' => 'string', 'description' => 'Palabras clave del producto (tipo, marca, uso), sin frases completas']], ['query']);
            $tools[] = $fn('product_detail', 'Obtiene la ficha de un producto concreto del catálogo por su id (de product_search) para responder dudas sobre él.',
                ['product_id' => ['type' => 'string', 'description' => 'Id del producto']], ['product_id']);
        }
        if ($withCart) {
            $tools[] = $fn('show_cart', 'Muestra lo que el cliente tiene ahora en su cesta de la tienda (productos, cantidades y total).', []);
            $tools[] = $fn('add_to_cart', 'Añade un producto a la cesta del cliente. Úsala SOLO si el cliente ha pedido o confirmado explícitamente en su último mensaje añadir ESE producto y cantidad; si no, pregúntale antes y no la llames.',
                [
                    'product_id' => ['type' => 'string', 'description' => 'Id del producto (de product_search)'],
                    'quantity' => ['type' => 'integer', 'description' => 'Unidades (1 si no lo dice)'],
                    'customer_confirmed' => ['type' => 'boolean', 'description' => 'true solo si el cliente lo pidió/confirmó expresamente'],
                ], ['product_id', 'customer_confirmed']);
        }

        return $tools;
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
                ], JSON_UNESCAPED_UNICODE);
            }

            if ($name === 'add_to_cart' && $cart !== null && $catalog !== null) {
                if (($args['customer_confirmed'] ?? false) !== true) {
                    return 'No añadido: primero pregunta al cliente si quiere añadir ese producto a su cesta.';
                }
                $product = $catalog->find(trim((string) ($args['product_id'] ?? '')));
                if ($product === null || ! $product->available) {
                    return 'No añadido: el producto no existe o no está disponible.';
                }
                $shown[(string) $product->id] = $product;
                if ($product->hasCombinations ?? false) {
                    return 'No añadido: el producto tiene opciones (talla, color...). Se le muestra la tarjeta para que las elija en la ficha.';
                }

                $result = $cart->add((int) $product->id, (int) ($product->idProductAttribute ?? 0), max(1, (int) ($args['quantity'] ?? 1)));

                return ($result['ok'] ?? false)
                    ? 'Añadido a la cesta del cliente.'
                    : 'No se pudo añadir desde aquí; el cliente puede usar el botón Añadir de la tarjeta.';
            }

            if ($name === 'product_search' && $catalog !== null) {
                $products = array_slice($catalog->search(trim((string) ($args['query'] ?? '')), self::MAX_PRODUCTS), 0, self::MAX_PRODUCTS);
                foreach ($products as $p) {
                    $shown[(string) $p->id] = $p;
                }

                return $products === []
                    ? 'No hay productos en el catálogo que coincidan con esa búsqueda.'
                    : json_encode(array_map(fn ($p) => [
                        'id' => (string) $p->id,
                        'title' => $this->sanitize((string) $p->title),
                        'price' => $p->price,
                        'currency' => $p->currency,
                        'available' => $p->available,
                        'has_options' => (bool) ($p->hasCombinations ?? false),
                    ], $products), JSON_UNESCAPED_UNICODE);
            }

            if ($name === 'product_detail' && $catalog !== null) {
                $product = $catalog->find(trim((string) ($args['product_id'] ?? '')));
                if ($product === null) {
                    return 'No se encontró ese producto en el catálogo.';
                }
                $shown[(string) $product->id] = $product;

                return json_encode([
                    'id' => (string) $product->id,
                    'title' => $this->sanitize((string) $product->title),
                    'description' => $this->sanitize((string) ($product->description ?? '')),
                    'price' => $product->price,
                    'currency' => $product->currency,
                    'available' => $product->available,
                    'has_options' => (bool) ($product->hasCombinations ?? false),
                ], JSON_UNESCAPED_UNICODE);
            }

            if ($name === 'lookup_order') {
                // Defensa en profundidad: exige customer_identified_via_otp,
                // no el customer_identified genérico — un nodo identify_customer
                // con require_otp=false (footgun de configuración documentado
                // en ChatFlowIdentityOtp) también marca customer_identified,
                // pero solo a partir de un email/teléfono/NIF escrito en texto
                // libre por el usuario, sin verificar que sea realmente suyo.
                // Sin este distingo, cualquiera podía "identificarse" como un
                // tercero y este tool le exponía sus pedidos (mismo IDOR que
                // el OTP existe para cerrar).
                if (empty($context['customer_identified_via_otp'])) {
                    return 'El cliente aún no ha verificado su identidad, no puedo consultar sus pedidos.';
                }

                $order = $this->orderLookup->lookup($args['order_id'] ?? null, [
                    'erp_id' => $context['customer_erp_id'] ?? null,
                    'ps_id' => $context['customer_ps_id'] ?? null,
                    'email' => $context['customer_email'] ?? null,
                ]);

                return $order['found']
                    ? json_encode(['status' => $order['status'], 'total' => $order['total'], 'tracking' => $order['tracking']], JSON_UNESCAPED_UNICODE)
                    : 'No se encontró el pedido en la cuenta del cliente.';
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
