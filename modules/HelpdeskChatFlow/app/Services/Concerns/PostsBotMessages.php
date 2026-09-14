<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Single source of truth for persisting a bot-authored message on the
 * conversation timeline. Centralises the `sent_by_chatflow` + `flow_node_id`
 * marker that the inbox and analytics rely on to tell bot turns apart from
 * agent/customer ones — previously duplicated across ~27 call sites in the
 * flow engine and node executor.
 */
trait PostsBotMessages
{
    /**
     * @param  array<string, mixed>  $metadata  Extra metadata merged on top of the bot marker.
     * @param  array<string, mixed>  $attributes  Extra column values (e.g. `attachment_urls`).
     */
    protected function postBotMessage(
        ?Conversation $conversation,
        ?string $nodeId,
        string $body,
        array $metadata = [],
        array $attributes = [],
    ): ?ConversationItem {
        return $conversation?->items()->create([
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            ...$attributes,
            'metadata' => [
                'sent_by_chatflow' => true,
                'flow_node_id' => $nodeId,
                ...$metadata,
            ],
        ]);
    }
}
