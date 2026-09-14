<?php

namespace Modules\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\TrustedDevice;

class NewDeviceDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly TrustedDevice $device,
    ) {}
}
