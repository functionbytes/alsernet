<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Engagement\Models\VisitorSession;

Broadcast::channel('widget-session.{sessionToken}', function ($user, string $sessionToken) {
    return VisitorSession::query()->where('session_token', $sessionToken)->exists();
});

Broadcast::channel('engagement.visitors', function ($user) {
    return $user && $user->can('engagement.events.view');
});
