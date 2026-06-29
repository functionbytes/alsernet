<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;

/**
 * Generates an AI summary of the bot conversation when it is handed off to a
 * human agent, so the agent has instant context (what the customer wanted, what
 * the bot did, data collected). Posted as an internal note. Competitors like
 * Intercom and Zendesk ship this as "conversation summary".
 */
class ChatFlowHandoffSummary
{
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

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->retry(1, 400, throw: false)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'max_tokens' => 300,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $transcript],
                    ],
                ]);

            if ($response->failed()) {
                return null;
            }

            $summary = trim((string) $response->json('choices.0.message.content'));

            return $summary !== '' ? $summary : null;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowHandoffSummary: failed', ['error' => $e->getMessage()]);

            return null;
        }
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
