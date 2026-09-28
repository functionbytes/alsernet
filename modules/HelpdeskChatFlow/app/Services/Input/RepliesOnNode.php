<?php

namespace Modules\HelpdeskChatFlow\Services\Input;

use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;

/**
 * Shared plumbing for the input handlers: bot replies on the current node,
 * localisation (when the handler has a `$localizer`) and graph navigation.
 */
trait RepliesOnNode
{
    use PostsBotMessages;

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $extraMetadata
     */
    private function sendBotMessage(ChatFlowSession $session, array $node, string $body, array $extraMetadata = []): void
    {
        $this->postBotMessage($session->conversation, $node['id'] ?? null, $body, $extraMetadata);
    }

    /**
     * Translate a fixed customer-facing string into the customer's detected
     * language (multilingual flows); a no-op when no language was detected.
     */
    private function localize(ChatFlowSession $session, string $text): string
    {
        return $this->localizer->localize($text, $session->getContextValue('customer_lang'));
    }

    private function firstChildId(ChatFlowSession $session, string $parentId): ?string
    {
        return $session->chatFlow->childrenByParent()[$parentId][0]['id'] ?? null;
    }
}
