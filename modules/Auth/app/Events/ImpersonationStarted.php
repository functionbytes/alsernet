<?php

namespace Modules\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImpersonationStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $impersonator,
        public readonly User $impersonated,
        public readonly string $ipAddress,
    ) {}
}
