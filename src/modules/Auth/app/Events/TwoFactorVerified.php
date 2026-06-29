<?php

namespace Modules\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TwoFactorVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $ipAddress,
        public readonly string $method = 'otp',
    ) {}
}
