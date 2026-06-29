<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * An autonomous AI agent (function/tool calling): given the customer message and
 * context, the LLM decides which tool to call — look up an order, search the help
 * center, answer, or escalate — and we run it, feeding results back until the
 * agent answers or escalates. The "agentic" pattern competitors ship in 2026.
 */
class ChatFlowAgentService
{
    private const MAX_STEPS = 4;

    /**
     * @param  object|null  $embeddings  HelpdeskHelpcenter EmbeddingsService (optional)
     */
    public function __construct(
        private readonly ChatFlowOrderLookup $orderLookup,
        private readonly ?object $embeddings = null,
    ) {}

    /**
     * @param  array<string,mixed>  $context  Session context (customer_*, etc.)
     * @param  array<string,mixed>  $data  Node data (instructions, tools enabled)
     * @return array{action: string, text: string, used_tools: array<int,string>}
     */
    public function run(string $question, array $context, array $data, string $locale = 'es'): array
    {
        $apiKey = config('services.openai.key', '');
        if (empty($apiKey) || trim($question) === '') {
            return ['action' => 'escalate', 'text' => $data['fallback_message'] ?? 'Te paso con un agente.', 'used_tools' => []];
        }

        $tools = $this->buildTools($data);
        $system = trim($data['instructions'] ?? 'Eres un agente de atención al cliente. Usa las herramientas disponibles cuando ayuden a resolver la consulta. Responde de forma breve y amable.');
        $lang = strtolower(substr($locale, 0, 2));
        if ($lang !== 'es') {
            $system .= " Responde SIEMPRE en el idioma del cliente (ISO: {$lang}).";
        }

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $question],
        ];
        $usedTools = [];

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $message = $this->callLlm($apiKey, $messages, $tools, $data);

            if ($message === null) {
                return ['action' => 'escalate', 'text' => $data['fallback_message'] ?? 'Te paso con un agente.', 'used_tools' => $usedTools];
            }

            $toolCalls = $message['tool_calls'] ?? [];

            if (empty($toolCalls)) {
                return ['action' => 'respond', 'text' => trim((string) ($message['content'] ?? '')), 'used_tools' => $usedTools];
            }

            $messages[] = $message; // assistant turn with tool_calls

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $usedTools[] = $name;

                if ($name === 'answer_customer') {
                    return ['action' => 'respond', 'text' => trim((string) ($args['text'] ?? '')), 'used_tools' => $usedTools];
                }
                if ($name === 'escalate_to_agent') {
                    return ['action' => 'escalate', 'text' => trim((string) ($args['message'] ?? 'Te paso con un agente.')), 'used_tools' => $usedTools];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $this->executeTool($name, $args, $context),
                ];
            }
        }

        return ['action' => 'escalate', 'text' => 'Te paso con un agente para ayudarte mejor.', 'used_tools' => $usedTools];
    }

    /**
     * @param  array<int, array<string,mixed>>  $messages
     * @param  array<int, array<string,mixed>>  $tools
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>|null
     */
    private function callLlm(string $apiKey, array $messages, array $tools, array $data): ?array
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(40)
                ->retry(1, 400, throw: false)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $data['model'] ?? config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
                    'temperature' => 0.3,
                    'messages' => $messages,
                    'tools' => $tools,
                ]);

            if ($response->failed()) {
                Log::warning('ChatFlowAgentService: LLM call failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('choices.0.message');
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAgentService: exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<int, array<string,mixed>>
     */
    private function buildTools(array $data): array
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

        return $tools;
    }

    /**
     * @param  array<string,mixed>  $args
     * @param  array<string,mixed>  $context
     */
    private function executeTool(string $name, array $args, array $context): string
    {
        try {
            if ($name === 'lookup_order') {
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
                $chunks = collect($results)->map(fn ($r) => $r['chunk_text'] ?? '')->filter()->take(3)->implode("\n---\n");

                return $chunks !== '' ? $chunks : 'No se encontró información relevante en el centro de ayuda.';
            }
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAgentService: tool failed', ['tool' => $name, 'error' => $e->getMessage()]);

            return 'La herramienta no está disponible en este momento.';
        }

        return 'Herramienta desconocida.';
    }
}
