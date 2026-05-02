<?php

namespace Modules\Helpdesk\Services\AI;

use Modules\Helpdesk\Models\Conversation;

class SuggestReplyService
{
    public function __construct(
        private readonly AiClient $client
    ) {}

    /**
     * Suggest reply texts for a conversation.
     *
     * @return string[]
     */
    public function suggest(Conversation $conversation, int $count = 3): array
    {
        if (! $this->client->isEnabled()) {
            return [];
        }

        $context = $this->buildContext($conversation);

        $systemPrompt = <<<'PROMPT'
Eres un agente de soporte al cliente profesional y empático. Tu objetivo es sugerir respuestas
cortas, claras y directas al cliente. Usa un tono profesional pero cercano.
Responde SOLO con un array JSON de strings. Sin explicaciones ni texto adicional.
Ejemplo: ["Respuesta 1", "Respuesta 2", "Respuesta 3"]
PROMPT;

        $userPrompt = "Genera exactamente {$count} sugerencias de respuesta para este caso de soporte.\n\n"
            .$context;

        $raw = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        return $this->parseJsonArray($raw, $count);
    }

    private function buildContext(Conversation $conversation): string
    {
        $conversation->loadMissing('customer');

        $customerName = $conversation->customer?->name ?? 'Cliente';
        $channel = $conversation->channel ?? 'web';
        $subject = $conversation->subject ?? 'Sin asunto';

        $recentItems = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest()
            ->limit(10)
            ->get()
            ->reverse();

        $thread = $recentItems->map(function ($item) {
            $role = $item->isFromAgent() ? 'Agente' : 'Cliente';

            return "[{$role}]: ".strip_tags($item->body ?? '');
        })->implode("\n");

        return <<<TEXT
Canal: {$channel}
Asunto: {$subject}
Cliente: {$customerName}

Últimos mensajes:
{$thread}
TEXT;
    }

    /**
     * @return string[]
     */
    private function parseJsonArray(?string $raw, int $expected): array
    {
        if (empty($raw)) {
            return [];
        }

        // Extract JSON array from the response (handle markdown code blocks)
        if (preg_match('/\[.*\]/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_string'));
            }
        }

        return [];
    }
}
