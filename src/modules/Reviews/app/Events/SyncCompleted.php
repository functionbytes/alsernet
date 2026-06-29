<?php

namespace Modules\Reviews\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $locationId,
        public readonly int $syncedCount,
        public readonly Carbon $completedAt
    ) {}
}
