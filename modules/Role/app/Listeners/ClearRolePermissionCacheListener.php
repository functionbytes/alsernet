<?php

namespace Modules\Role\Listeners;

use Spatie\Permission\PermissionRegistrar;

class ClearRolePermissionCacheListener
{
    public function handle(mixed $event): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
