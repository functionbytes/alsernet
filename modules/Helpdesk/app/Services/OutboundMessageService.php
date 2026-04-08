<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;

class OutboundMessageService
{
    public function __construct(
        private readonly WhatsAppBusinessService $whatsapp,
        private readonly FacebookMessengerService $facebook,
        private readonly InstagramService $instagram,
    ) {}

    /**
     * Send a plain-text reply from an agent to the customer via the conversation's channel.
     *
     * Returns the external message ID (wamid / message_id) or null if not applicable
     * (widget conversations, disabled channels, or errors).
     */
    public function sendReply(Conversation $conversation, string $text): ?string
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'widget') {
            return null; // Widget conversations don't need outbound API calls
        }

        try {
            return match ($channel) {
                'whatsapp' => $this->whatsapp->sendText($externalId, $text),
                'facebook' => $this->facebook->sendText($externalId, $text),
                'instagram' => $this->instagram->sendText($externalId, $text),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: failed to send reply', [
                'channel' => $channel,
                'external_id' => $externalId,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Whether the given conversation supports outbound sending via an external API.
     */
    public function supports(Conversation $conversation): bool
    {
        return in_array($conversation->channel ?? 'widget', ['whatsapp', 'facebook', 'instagram'], true)
            && filled($conversation->external_sender_id);
    }
}
