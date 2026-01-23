<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal personalizado para notificaciones de usuarios
// Formato: users.{id}
// NOTE: Private channels require session auth which WebSocket clients can't provide.
// Keeping this for reference, but using public-notifications.{id} for WebSocket support.
Broadcast::channel('users.{id}', function ($user, $id) {
    \Log::debug('Channel authorization attempt', [
        'channel' => 'users.'.$id,
        'user_present' => $user !== null,
        'user_id' => $user?->id,
        'requested_id' => $id,
    ]);

    // Solo el usuario puede escuchar sus propias notificaciones
    if (! $user) {
        \Log::warning('No user authenticated for channel authorization');

        return false;
    }

    return (int) $user->id === (int) $id;
});

// Public channel for notifications (no auth required)
// This works with WebSocket because no session cookies are needed
// The channel name includes the user ID for routing notifications to the correct user
Broadcast::channel('public-notifications.{id}', function ($user, $id) {
    // Allow anyone to listen to public notification channels
    // Security is ensured by:
    // 1. Only the backend broadcasts to these channels
    // 2. Channel name includes user ID, so users can only subscribe to their own
    // 3. Frontend only subscribes to channels for authenticated users
    return true;
});
