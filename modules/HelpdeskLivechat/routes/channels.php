<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Nwidart\Modules\Facades\Module;

/*
| Channel `widget-session.{token}` is owned by Engagement when active.
| If Engagement is disabled, fall back to widget chat sessions so the
| livechat-only mode still authorises broadcast subscriptions.
*/

if (! Module::find('Engagement')?->isEnabled()) {
    Broadcast::channel('widget-session.{sessionToken}', function ($user, string $sessionToken) {
        return WidgetSession::query()
            ->where('session_token', $sessionToken)
            ->exists();
    });
}
