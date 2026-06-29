<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\ReviewGoogleConnection;

class ConnectionRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReviewGoogleConnection $connection
    ) {}
}
