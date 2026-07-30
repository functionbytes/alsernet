<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Sends proactive (outbound) WhatsApp bot messages as approved HSM templates
 * when the conversation is outside the 24h customer-service window — WhatsApp
 * rejects free-form text there. Reuses the helpdesk's WhatsAppHsmService; when
 * unavailable, callers fall back to a normal text send.
 */
class ChatFlowHsmDelivery
{
    /**
     * @param  object|null  $hsmService  Helpdesk WhatsAppHsmService with send(Conversation, string, array): array
     */
    public function __construct(
        private readonly ?object $hsmService = null,
    ) {}

    /**
     * Whether this message should be delivered as an HSM template: WhatsApp
     * channel, a template configured on the node, and the session window closed.
     *
     * @param  array<string, mixed>  $meta
     */
    public function shouldUseTemplate(Conversation $conversation, array $meta): bool
    {
        return $this->hsmService !== null
            && ($conversation->channel ?? null) === 'whatsapp'
            && ! empty($meta['whatsapp_template'])
            && $this->outsideSessionWindow($conversation);
    }

    /**
     * Send the HSM template. Returns the external message id, or null on failure.
     *
     * @param  array<string, mixed>  $meta
     */
    public function send(Conversation $conversation, array $meta): ?string
    {
        try {
            $result = $this->hsmService->send(
                $conversation,
                (string) $meta['whatsapp_template'],
                array_values($meta['template_vars'] ?? []),
            );

            return is_array($result) ? ($result['id'] ?? null) : null;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowHsmDelivery: template send failed', [
                'conversation_id' => $conversation->id,
                'template' => $meta['whatsapp_template'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * True when the last inbound customer message is older than 24h (or there is
     * none), so only template messages are allowed by WhatsApp.
     */
    private function outsideSessionWindow(Conversation $conversation): bool
    {
        $lastInboundAt = ConversationItem::on('helpdesk')
            ->where('conversation_id', $conversation->id)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
            ->latest('id')
            ->value('created_at');

        return $lastInboundAt === null || $lastInboundAt->lt(now()->subHours(24));
    }
}
