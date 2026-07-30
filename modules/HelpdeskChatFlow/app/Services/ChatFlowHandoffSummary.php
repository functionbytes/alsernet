<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\AI\AiClient;

/**
 * Generates an AI summary of the bot conversation when it is handed off to a
 * human agent, so the agent has instant context (what the customer wanted, what
 * the bot did, data collected). Posted as an internal note. Competitors like
 * Intercom and Zendesk ship this as "conversation summary".
 *
 * OpenAI traffic goes through the core {@see AiClient} gateway (CFM-S5).
 */
class ChatFlowHandoffSummary
{
    private readonly ?AiClient $aiClient;

    public function __construct(?AiClient $aiClient = null)
    {
        $this->aiClient = $aiClient ?? (class_exists(AiClient::class) ? new AiClient : null);
    }

    /**
     * Build the summary and post it as an internal note on the conversation.
     */
    public function postFor(Conversation $conversation): void
    {
        $summary = $this->generate($conversation);

        if ($summary === null) {
            return;
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => "🤖 Resumen del bot para el agente:\n\n".$summary,
            'is_internal' => true,
            'metadata' => ['sent_by_chatflow' => true, 'handoff_summary' => true],
        ]);
    }

    public function generate(Conversation $conversation): ?string
    {
        $apiKey = config('services.openai.key', '');
        if (empty($apiKey)) {
            return null;
        }

        $transcript = $this->transcript($conversation);
        if (trim($transcript) === '') {
            return null;
        }

        $system = 'Resume esta conversación de atención al cliente para el agente humano que la va a continuar. '
            .'Sé breve y usa viñetas con: qué quería el cliente, qué hizo el bot, datos recogidos y qué queda pendiente. '
            .'Responde en español.';

        $message = $this->aiClient?->chatCompletion([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $transcript],
        ], [
            'model' => config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
            'temperature' => 0.2,
            'max_tokens' => 300,
            'timeout' => 30,
            'retries' => 1,
            'retry_delay' => 400,
        ]);

        if (! is_array($message)) {
            return null;
        }

        $summary = trim((string) ($message['content'] ?? ''));

        return $summary !== '' ? $summary : null;
    }

    private function transcript(Conversation $conversation): string
    {
        return $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(function ($item) {
                $who = ($item->metadata['sent_by_chatflow'] ?? false) ? 'Bot' : 'Cliente';

                return "{$who}: ".trim(strip_tags((string) $item->body));
            })
            ->implode("\n");
    }
}
