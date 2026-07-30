<?php

namespace Modules\Helpdesk\Concerns;

use Illuminate\Broadcasting\Channel;
use Modules\Helpdesk\Models\Conversation;

/**
 * Builds the token-guarded public channel the Livechat widget subscribes to.
 *
 * The conversation carries a cryptographically random `widget_pubsub_token` in
 * its metadata, handed to the visitor only at creation. Embedding that token in
 * the channel name (`helpdesk-widget-conversation.{id}.{pubsubToken}`) makes the
 * otherwise sequential conversation id unguessable, closing the realtime IDOR
 * present on the legacy token-less `helpdesk-widget-conversation.{id}` channel
 * where any visitor could subscribe to another conversation's agent replies and
 * typing signals.
 */
trait BroadcastsToWidgetConversation
{
    protected function widgetConversationChannel(Conversation $conversation): ?Channel
    {
        $metadata = $conversation->metadata;
        if (! is_array($metadata)) {
            $metadata = json_decode((string) ($metadata ?? '{}'), true) ?: [];
        }

        $token = $metadata['widget_pubsub_token'] ?? null;
        if (! is_string($token) || $token === '') {
            return null;
        }

        return new Channel('helpdesk-widget-conversation.'.$conversation->id.'.'.$token);
    }
}
