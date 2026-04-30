<?php

use Modules\Reverb\Services\ChannelRegistry;

/*
|--------------------------------------------------------------------------
| Chat Module — Broadcast Channels
|--------------------------------------------------------------------------
|
| Loaded automatically by Modules\Reverb\Providers\ReverServiceProvider.
|
*/

// Private: authenticated agents subscribe to real-time conversation events
// (messages, typing indicators, status changes, assignments)
ChannelRegistry::register(
    'conversation.{conversationId}',
    fn ($user) => auth()->check(),
    ['module' => 'Chat', 'type' => 'private', 'description' => 'Real-time conversation events for authenticated agents']
);

// Private: account-wide events (conversation updates, bulk assignments, reports)
ChannelRegistry::register(
    'account.{accountId}',
    fn ($user, $accountId) => auth()->check() && (int) $user->account_id === (int) $accountId,
    ['module' => 'Chat', 'type' => 'private', 'description' => 'Account-scoped broadcast events']
);

// Public: unauthenticated widget users receive messages in their session
ChannelRegistry::register(
    'widget-conversation.{conversationId}',
    null, // public — no auth required
    ['module' => 'Chat', 'type' => 'public', 'description' => 'Widget user message delivery']
);
