<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Nwidart\Modules\Facades\Module;

/*
| Channel `widget-session.{token}` is owned by Engagement when active.
| If Engagement is disabled, fall back to widget chat sessions so the
| livechat-only mode still authorises broadcast subscriptions.
|
| SECURITY MODEL — do not restrict this channel to authenticated users.
|
| The Engagement SDK subscribes to this channel as a *public* channel via
| `pusher.subscribe('widget-session.<token>')` (no `private-` prefix, no
| broadcast auth endpoint). The events emitted here (TriggerFired,
| ScoreThresholdCrossed) are visitor-facing personalisation signals and are
| intentionally sent to unauthenticated browser sessions.
|
| The session_token itself acts as the access credential: it is a random
| 32-char token generated server-side on first visit and stored only in the
| visitor's localStorage. An attacker would need to enumerate ~62^32 tokens
| to intercept another visitor's session, which is computationally infeasible.
|
| The channel authorization callback below simply confirms the token exists,
| preventing subscription to non-existent sessions (e.g. brute-force probes).
|
| FUTURE: If higher security is ever required, migrate to the same pattern used
| by `helpdesk-widget-conversation.{id}.{pubsubToken}` — include a secondary
| random token in the channel name to make it cryptographically unguessable
| without a dedicated broadcast-auth endpoint. Example channel name:
| `helpdesk-widget-session.{sessionId}.{pubsubToken}`
*/

if (! Module::find('Engagement')?->isEnabled()) {
    Broadcast::channel('widget-session.{sessionToken}', function ($user, string $sessionToken) {
        return WidgetSession::query()
            ->where('session_token', $sessionToken)
            ->exists();
    });
}

/*
| Widget conversation channel.
|
| The channel name includes a pubsub_token generated at conversation creation
| and stored in the conversation's metadata JSON column. The widget receives this
| token in the createConversation API response and uses it to build the channel
| name: `helpdesk-widget-conversation.{conversationId}.{pubsubToken}`.
|
| This makes the channel name cryptographically unguessable even when the
| conversation ID is sequential — no dedicated broadcast auth endpoint required.
| The channel remains technically public, but guessing a 32-char random token is
| computationally infeasible, providing equivalent security to a private channel.
*/
Broadcast::channel('helpdesk-widget-conversation.{conversationId}.{pubsubToken}', function ($user, string $conversationId, string $pubsubToken) {
    $storedToken = Cache::remember(
        'helpdesklivechat:conv_pubsub:'.(int) $conversationId,
        now()->addSeconds(60),
        function () use ($conversationId): ?string {
            $conversation = Conversation::find((int) $conversationId);
            if (! $conversation) {
                return null;
            }
            $meta = is_array($conversation->metadata)
                ? $conversation->metadata
                : (json_decode((string) ($conversation->metadata ?? '{}'), true) ?? []);

            return $meta['widget_pubsub_token'] ?? null;
        }
    );

    // Constant-time comparison to prevent timing attacks.
    return $storedToken !== null && hash_equals($storedToken, $pubsubToken);
});

/*
| Typing indicator channel for the agent panel.
|
| Auth flow:
|  - Authenticated agents (panel) can subscribe to any conversation they have
|    access to via the existing helpdesk policies.
|  - Visitors do not subscribe to this channel — they receive typing events on
|    the per-conversation pubsub channel above.
*/
Broadcast::channel('helpdesk.conversation.{conversationId}.typing', function ($user, string $conversationId) {
    if (! $user) {
        return false;
    }

    return Cache::remember(
        'helpdesklivechat:conv_exists:'.(int) $conversationId,
        now()->addSeconds(60),
        fn (): bool => Conversation::query()->whereKey((int) $conversationId)->exists()
    );
});

/*
| Live view (rrweb) channel — agents only.
|
| Visitors push DOM mutation batches via REST; agents subscribe here to receive
| broadcast updates. Authorization requires authenticated user with the
| `helpdesk.conversations.view` permission and an existing conversation.
*/
Broadcast::channel('livestream.conversation.{conversationId}', function ($user, string $conversationId) {
    if (! $user || ! $user->can('helpdesk.conversations.view')) {
        return false;
    }

    return Cache::remember(
        'helpdesklivechat:conv_exists:'.(int) $conversationId,
        now()->addSeconds(60),
        fn (): bool => Conversation::query()->whereKey((int) $conversationId)->exists()
    );
});

/*
| WebRTC signaling channel — agent side.
|
| Agents receive offer / ICE candidates from the visitor through this channel.
*/
Broadcast::channel('webrtc.conversation.{conversationId}', function ($user, string $conversationId) {
    if (! $user || ! $user->can('helpdesk.conversations.view')) {
        return false;
    }

    return Cache::remember(
        'helpdesklivechat:conv_exists:'.(int) $conversationId,
        now()->addSeconds(60),
        fn (): bool => Conversation::query()->whereKey((int) $conversationId)->exists()
    );
});

/*
| Visitor-facing WebRTC signals are broadcast on the existing public
| `helpdesk-widget-conversation.{id}.{pubsubToken}` channel which the widget
| already subscribes to for chat events. No additional authorization channel
| is required — see WebRtcSignal::broadcastOn() for the dispatch logic.
*/
