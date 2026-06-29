<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates an AI answer for a chat flow node, optionally grounded on the
 * help center knowledge base (RAG). Reuses HelpdeskHelpcenter's
 * EmbeddingsService when the module is installed.
 */
class ChatFlowAiResponder
{
    /**
     * @param  object|null  $embeddings  HelpdeskHelpcenter EmbeddingsService (optional)
     */
    public function __construct(
        private readonly ?object $embeddings = null,
    ) {}

    /**
     * @param  array<string,mixed>  $data  Node data (instructions, use_knowledge_base, ...)
     * @param  array<int, array{role: string, content: string}>  $history  Prior conversation turns
     * @return array{answer: string, used_kb: bool, sources: array<int, array{article_id: int, similarity: float}>}
     */
    public function generate(string $question, array $data, string $locale = 'es', array $history = []): array
    {
        $question = trim($question);
        $useKb = (bool) ($data['use_knowledge_base'] ?? true);

        $context = '';
        $sources = [];

        if ($useKb && $this->embeddings !== null && $question !== '') {
            [$context, $sources] = $this->retrieveKnowledge($question, $data, $locale);
        }

        $answer = $this->callLlm($question, $data, $context, $history, $locale);

        if ($answer === null || trim($answer) === '') {
            $answer = $data['fallback_message']
                ?? 'Lo siento, no he podido generar una respuesta en este momento. Te paso con un agente.';
        }

        return [
            'answer' => trim($answer),
            'used_kb' => $context !== '',
            'sources' => $sources,
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array{0: string, 1: array<int, array{article_id: int, similarity: float}>}
     */
    private function retrieveKnowledge(string $question, array $data, string $locale): array
    {
        $limit = (int) ($data['kb_results'] ?? config('helpdeskchatflow.ai.kb_results', 4));
        $minSimilarity = (float) config('helpdeskchatflow.ai.min_similarity', 0.2);

        try {
            $results = $this->embeddings->search($question, $limit, $locale);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAiResponder: KB search failed', ['error' => $e->getMessage()]);

            return ['', []];
        }

        $relevant = array_values(array_filter(
            $results,
            fn ($r) => ($r['similarity'] ?? 0) >= $minSimilarity
        ));

        if (empty($relevant)) {
            return ['', []];
        }

        $context = collect($relevant)
            ->map(fn ($r) => '- '.trim($r['chunk_text'] ?? ''))
            ->implode("\n");

        $sources = array_map(fn ($r) => [
            'article_id' => (int) ($r['article_id'] ?? 0),
            'similarity' => round((float) ($r['similarity'] ?? 0), 3),
        ], $relevant);

        return [$context, $sources];
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function callLlm(string $question, array $data, string $context, array $history = [], string $locale = 'es'): ?string
    {
        $apiKey = config('services.openai.key', '');

        if (empty($apiKey)) {
            Log::warning('ChatFlowAiResponder: OPENAI_API_KEY not configured.');

            return null;
        }

        $instructions = trim($data['instructions'] ?? 'Eres un asistente de atención al cliente. Responde de forma breve, clara y amable.');

        // Reply in the customer's language natively (no translation layer).
        $lang = strtolower(substr($locale, 0, 2));
        if ($lang !== 'es') {
            $instructions .= "\n\nIMPORTANTE: responde SIEMPRE en el idioma del cliente (código ISO: {$lang}).";
        }

        if ($context !== '') {
            $instructions .= "\n\nUtiliza ÚNICAMENTE la siguiente información del centro de ayuda para responder. "
                ."Si la respuesta no está en esta información, dilo honestamente y ofrece pasar con un agente.\n\n"
                ."=== Información del centro de ayuda ===\n{$context}";
        }

        $model = $data['model'] ?? config('helpdeskchatflow.ai.model', 'gpt-4o-mini');
        $temperature = (float) ($data['temperature'] ?? config('helpdeskchatflow.ai.temperature', 0.3));
        $maxTokens = (int) config('helpdeskchatflow.ai.max_tokens', 500);

        $messages = array_merge(
            [['role' => 'system', 'content' => $instructions]],
            $this->sanitizeHistory($history),
            [['role' => 'user', 'content' => $question !== '' ? $question : 'Hola']],
        );

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->retry(2, 500, throw: false)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'messages' => $messages,
                ]);

            if ($response->failed()) {
                Log::warning('ChatFlowAiResponder: OpenAI call failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAiResponder: exception calling OpenAI', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Classify a free-text reply to the option (1-based index) that best matches
     * the customer's intent, or null when nothing matches / no API key. Powers the
     * "understand natural language" mode on quick_replies nodes.
     *
     * @param  array<int, string>  $options
     */
    public function classifyIntent(string $message, array $options): ?int
    {
        $message = trim($message);
        $options = array_values($options);

        if ($message === '' || empty($options)) {
            return null;
        }

        $apiKey = config('services.openai.key', '');
        if (empty($apiKey)) {
            return null;
        }

        $list = implode("\n", array_map(fn ($i, $o) => ($i + 1).'. '.$o, array_keys($options), $options));
        $system = 'Eres un clasificador de intención para un chatbot de atención al cliente. '
            .'El usuario escribió un mensaje libre. Devuelve SOLO el número de la opción que mejor '
            ."coincide con su intención, o 0 si ninguna coincide con claridad.\n\nOpciones:\n{$list}";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->retry(1, 300, throw: false)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
                    'temperature' => 0,
                    'max_tokens' => 5,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $message],
                    ],
                ]);

            if ($response->failed()) {
                return null;
            }

            preg_match('/\d+/', (string) $response->json('choices.0.message.content'), $m);
            $num = isset($m[0]) ? (int) $m[0] : 0;

            return ($num >= 1 && $num <= count($options)) ? $num : null;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowAiResponder: classifyIntent failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Keep only valid user/assistant turns with non-empty content, capped to the
     * most recent ones to bound token usage.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
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
}
