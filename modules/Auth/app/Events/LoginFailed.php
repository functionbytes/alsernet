<?php

namespace Modules\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?string $email,
        public readonly string $ipAddress,
        public readonly ?string $userAgent = null,
        public readonly ?User $user = null,
        public readonly string $reason = 'invalid_credentials',
    ) {}
}
