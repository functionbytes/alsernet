<?php

namespace Modules\Helpdesk\Services\Public;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\OutboundMessageService;

/**
 * Drop-in replacement for {@see OutboundMessageService} used ONLY when the public
 * simulator is enabled (see HelpdeskServiceProvider::registerSimulatorOutboundGuard).
 *
 * It short-circuits every outbound operation for simulated conversations so that
 * an agent reply NEVER triggers a real WhatsApp / Facebook / Instagram Graph API
 * call — even though the conversation keeps its real channel slug for inbox
 * realism. The message is still persisted and broadcast to the public panel by
 * the regular pipeline; only the external API hop is suppressed.
 *
 * For every non-simulated conversation it behaves exactly like the parent.
 */
class SimulatorOutboundMessageService extends OutboundMessageService
{
    public function supports(Conversation $conversation): bool
    {
        if ($this->isSimulated($conversation)) {
            return false;
        }

        return parent::supports($conversation);
    }

    public function sendReply(Conversation $conversation, string $text, bool $fast = false): ?string
    {
        return $this->isSimulated($conversation) ? null : parent::sendReply($conversation, $text, $fast);
    }

    public function sendAttachment(Conversation $conversation, string $type, string $url, ?string $caption = null, ?string $filename = null): ?string
    {
        return $this->isSimulated($conversation)
            ? null
            : parent::sendAttachment($conversation, $type, $url, $caption, $filename);
    }

    public function sendQuickReplies(Conversation $conversation, string $text, array $buttons): ?string
    {
        return $this->isSimulated($conversation) ? null : parent::sendQuickReplies($conversation, $text, $buttons);
    }

    public function setTyping(Conversation $conversation, bool $on): bool
    {
        return $this->isSimulated($conversation) ? false : parent::setTyping($conversation, $on);
    }

    public function markSeen(Conversation $conversation, ?string $externalMessageId = null): bool
    {
        return $this->isSimulated($conversation) ? false : parent::markSeen($conversation, $externalMessageId);
    }

    /**
     * A conversation is treated as simulated if it carries metadata.is_simulator,
     * or while the simulator is actively injecting an inbound message in this
     * request (covers automations that fire before the metadata flag is written).
     */
    private function isSimulated(Conversation $conversation): bool
    {
        return SimulatorContext::active()
            || data_get($conversation->metadata, 'is_simulator') === true;
    }
}
