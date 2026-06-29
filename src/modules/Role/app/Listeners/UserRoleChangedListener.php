<?php

namespace Modules\Role\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Role\Events\UserRoleChanged;
use Modules\Role\Notifications\UserRoleChangedNotification;
use Spatie\Permission\PermissionRegistrar;

class UserRoleChangedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $backoff = 5;

    public function handle(UserRoleChanged $event): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $event->user->notify(new UserRoleChangedNotification($event->role, $event->action));
    }

    public function failed(UserRoleChanged $event, \Throwable $exception): void
    {
        Log::error('UserRoleChangedListener failed', [
            'user_id' => $event->user->id,
            'role' => $event->role->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
