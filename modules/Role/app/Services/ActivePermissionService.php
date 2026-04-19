<?php

namespace Modules\Role\Services;

use Illuminate\Database\Eloquent\Collection;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;

class ActivePermissionService
{
    /**
     * Returns all permissions filtered to active modules only.
     * Permissions whose prefix has no matching module (core) are always included.
     */
    public function getActivePermissions(): Collection
    {
        $allModules = collect(Module::all());

        $allModuleSlugs = $allModules
            ->map(fn ($m) => strtolower($m->getName()))
            ->values()
            ->toArray();

        $inactiveModuleSlugs = $allModules
            ->filter(fn ($m) => ! $m->isEnabled())
            ->map(fn ($m) => strtolower($m->getName()))
            ->values()
            ->toArray();

        return Permission::orderBy('name')
            ->get()
            ->filter(function (Permission $permission) use ($allModuleSlugs, $inactiveModuleSlugs) {
                $prefix = explode('.', $permission->name)[0] ?? '';

                // Core permission (no matching module) — always include
                if (! in_array($prefix, $allModuleSlugs, true)) {
                    return true;
                }

                return ! in_array($prefix, $inactiveModuleSlugs, true);
            })
            ->values();
    }
}
