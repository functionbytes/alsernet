<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Events\ImpersonationStarted;
use Modules\Auth\Notifications\ImpersonatedByAdminNotification;

class NotifyImpersonated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(ImpersonationStarted $event): void
    {
        $event->impersonated->notify(
            new ImpersonatedByAdminNotification($event->impersonator, $event->ipAddress)
        );
    }
}
