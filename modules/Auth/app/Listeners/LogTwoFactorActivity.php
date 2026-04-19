<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Events\TwoFactorFailed;
use Modules\Auth\Events\TwoFactorVerified;
use Modules\Auth\Models\LoginAttempt;

class LogTwoFactorActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function handleVerified(TwoFactorVerified $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'status' => LoginAttempt::STATUS_2FA_SUCCESS,
            'reason' => $event->method,
            'attempted_at' => now(),
        ]);
    }

    public function handleFailed(TwoFactorFailed $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'status' => LoginAttempt::STATUS_2FA_FAILED,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Register listener callbacks in the dispatcher.
     */
    public function subscribe($events): array
    {
        return [
            TwoFactorVerified::class => 'handleVerified',
            TwoFactorFailed::class => 'handleFailed',
        ];
    }
}
