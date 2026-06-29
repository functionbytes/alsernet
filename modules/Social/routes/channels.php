<?php

use Illuminate\Support\Facades\Broadcast;

/*
 *--------------------------------------------------------------------------
 * Broadcast Channels
 *--------------------------------------------------------------------------
 *
 * Here you may register all the event broadcasting channels that your
 * application supports. The given channel authorization callbacks are
 * used to check if an authenticated user can listen to the channel.
 *
*/

Broadcast::channel('account.{accountId}', function ($user, int $accountId): bool {
    return $user->account_id === $accountId;
});
