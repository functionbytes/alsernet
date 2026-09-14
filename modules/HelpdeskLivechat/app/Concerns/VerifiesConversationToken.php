<?php

namespace Modules\HelpdeskLivechat\Concerns;

use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;

/**
 * Per-conversation authorization for widget visitor requests.
 *
 * A conversation carries a cryptographically random `widget_pubsub_token` in
 * its metadata, handed to the visitor only at creation. Every widget REST call
 * that targets a specific conversation must echo it back via the
 * `X-Conversation-Token` header so the backend can prove the caller owns that
 * conversation — instead of trusting a guessable, sequential customer_id (IDOR)
 * or accepting access merely because the conversation belongs to the inbox.
 */
trait VerifiesConversationToken
{
    protected function conversationTokenValid(Conversation $conversation, Request $request): bool
    {
        $provided = trim((string) ($request->header('X-Conversation-Token') ?: $request->input('conversation_token', '')));
        if ($provided === '') {
            return false;
        }

        $metadata = $conversation->metadata;
        if (! is_array($metadata)) {
            $metadata = json_decode((string) ($metadata ?? '{}'), true) ?: [];
        }

        $expected = (string) ($metadata['widget_pubsub_token'] ?? '');

        return $expected !== '' && hash_equals($expected, $provided);
    }
}
