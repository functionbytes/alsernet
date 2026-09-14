<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\OutboundMessageService;

/**
 * Delivers a bot-authored message to the customer's external channel
 * (WhatsApp, Messenger, Instagram). Web/widget conversations are skipped:
 * they already receive bot messages over the broadcast channel.
 *
 * Options are always sent as a numbered text list (standardized by the flow),
 * so the same message works on every channel without depending on each
 * channel's native button limits.
 */
class BotMessageDispatcher
{
    public function __construct(
        private readonly OutboundMessageService $outbound,
        private readonly ChatFlowHsmDelivery $hsm,
    ) {}

    public function deliver(Conversation $conversation, ConversationItem $item): void
    {
        if (! $this->outbound->supports($conversation)) {
            return; // web/email or no external recipient — broadcast already covers web
        }

        $meta = $item->metadata ?? [];

        // Proactive WhatsApp outside the 24h window → must go as an approved HSM
        // template; a free-text send would be rejected by WhatsApp. The template is
        // the ONLY valid route here, so on failure we abort (and let the job retry)
        // instead of silently falling back to a free-text message the channel drops.
        if ($this->hsm->shouldUseTemplate($conversation, $meta)) {
            if ($this->hsm->send($conversation, $meta) !== null) {
                return;
            }

            Log::warning('ChatFlow HSM template delivery failed; aborting free-text fallback', [
                'conversation_id' => $conversation->id,
                'item_id' => $item->id,
            ]);

            throw new \RuntimeException("HSM template delivery failed for conversation {$conversation->id}");
        }

        // "Typing…" indicator on channels that support it (Messenger/Instagram).
        $this->outbound->setTyping($conversation, true);

        // Explicit file attachment (send_file node) → native attachment per channel.
        $attachment = $meta['attachment'] ?? null;
        if (! empty($attachment['url'])) {
            $sent = $this->outbound->sendAttachment(
                $conversation,
                $attachment['type'] ?? 'document',
                $attachment['url'],
                $attachment['caption'] ?? null,
            );

            $this->logIfDeliveryFailed($sent, $conversation, $item, 'attachment');

            return;
        }

        $body = trim((string) ($item->body ?? ''));

        // Carousel of product cards → native per channel (Messenger template,
        // WhatsApp/Instagram image cards). On success we're done; otherwise we
        // fall through to the numbered text body, which lists the same cards.
        $cards = $meta['cards'] ?? [];
        if (! empty($cards) && is_array($cards)) {
            $delivered = $this->outbound->sendCarousel($conversation, $cards) !== null;
            $options = $meta['bot_options'] ?? [];

            if ($delivered) {
                // If the node also expects a numbered selection, send the prompt.
                if (! empty($options) && $body !== '') {
                    $this->outbound->sendReply($conversation, $body);
                }

                return;
            }
        }

        $imageUrl = $meta['image_url'] ?? null;

        if ($imageUrl) {
            $this->outbound->sendAttachment($conversation, 'image', $imageUrl);

            // Avoid resending the raw image URL as a text line.
            if ($body === $imageUrl) {
                $body = '';
            }
        }

        // Options → try the channel's native buttons; fall back to the numbered text.
        $options = $meta['bot_options'] ?? [];
        if (! empty($options)) {
            $prompt = (string) ($meta['bot_prompt'] ?? $body);
            if ($this->outbound->sendOptions($conversation, $prompt, $options) !== null) {
                return; // native buttons delivered
            }
        }

        if ($body !== '') {
            $sent = $this->outbound->sendReply($conversation, $body);
            $this->logIfDeliveryFailed($sent, $conversation, $item, 'reply');
        }
    }

    /**
     * The channel sender returns null when the external send failed (the error is
     * swallowed inside OutboundMessageService). Surface it at the chatflow level so
     * a dropped bot message is visible in the logs rather than reported as success.
     */
    private function logIfDeliveryFailed(?string $result, Conversation $conversation, ConversationItem $item, string $kind): void
    {
        if ($result !== null) {
            return;
        }

        Log::warning('ChatFlow channel delivery returned null (message may not have been sent)', [
            'kind' => $kind,
            'conversation_id' => $conversation->id,
            'item_id' => $item->id,
        ]);
    }
}
