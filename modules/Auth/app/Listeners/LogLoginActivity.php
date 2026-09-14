<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\IpGeolocationService;

class LogLoginActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        private readonly IpGeolocationService $geo,
    ) {}

    public function handle(UserLoggedIn $event): void
    {
        $location = $this->geo->lookup($event->ipAddress);

        LoginAttempt::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'status' => LoginAttempt::STATUS_SUCCESS,
            'country' => $location['country'],
            'city' => $location['city'],
            'attempted_at' => now(),
        ]);
    }
}
