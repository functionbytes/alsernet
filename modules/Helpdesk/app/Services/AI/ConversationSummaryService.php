<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationSummaryService
{
    public function __construct(
        private readonly AiClient $client,
        private readonly PromptSanitizer $sanitizer,
    ) {}

    public function summarize(Conversation $conversation): ?string
    {
        if (! $this->client->isEnabled()) {
            return null;
        }

        $cacheKey = "helpdesk:ai:summary:{$conversation->id}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($conversation) {
            return $this->generateSummary($conversation);
        });
    }

    /**
     * Generate and persist an internal note with the AI summary.
     */
    public function summarizeAndPersist(Conversation $conversation): ?ConversationItem
    {
        $summary = $this->summarize($conversation);

        if (empty($summary)) {
            return null;
        }

        return ConversationItem::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'author_id' => null,
            'type' => 'note',
            'body' => '[AI summary] '.$summary,
            'is_internal' => true,
        ]);
    }

    private function generateSummary(Conversation $conversation): ?string
    {
        $items = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->orderBy('created_at')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $thread = $items->map(function ($item) {
            $role = $item->isFromAgent() ? 'Agente' : 'Cliente';
            $body = strip_tags($item->body ?? '');

            if (! $item->isFromAgent()) {
                $body = $this->sanitizer->sanitize($body);
            }

            return "[{$role}]: ".$body;
        })->implode("\n");

        $systemPrompt = 'Eres un asistente que analiza conversaciones de soporte al cliente. '
            .'Responde solo con el resumen solicitado, sin texto adicional. '
            .$this->sanitizer->systemGuard();

        $userPrompt = <<<TEXT
Analiza esta conversación de soporte y responde con:
1. Un resumen del caso en 2-3 frases.
2. Si quedó resuelto (sí/no y por qué).
3. Acciones pendientes si las hay.

Conversación:
{$thread}
TEXT;

        return $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }
}
