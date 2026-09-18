<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Events\NewDeviceDetected;
use Modules\Auth\Notifications\NewDeviceLoginNotification;

class SendNewDeviceAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(NewDeviceDetected $event): void
    {
        $event->user->notify(new NewDeviceLoginNotification($event->device));
    }
}
