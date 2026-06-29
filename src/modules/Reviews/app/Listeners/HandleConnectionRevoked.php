<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ConnectionRevoked;

class HandleConnectionRevoked
{
    public function handle(ConnectionRevoked $event): void
    {
        $connection = $event->connection;

        $deactivated = $connection->locations()->update(['is_active' => false]);

        Log::info('Connection revoked — locations deactivated', [
            'connection_id' => $connection->id,
            'google_email' => $connection->google_email,
            'user_id' => $connection->user_id,
            'locations_deactivated' => $deactivated,
        ]);
    }
}
