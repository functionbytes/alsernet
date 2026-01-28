<?php

namespace Modules\Mailrelay\Policies;

use App\Models\User;

trait HasSafePermissionCheck
{
    /**
     * Safely check if user has permission.
     * Returns true for super-admin, otherwise checks permission if it exists.
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        // Super-admin bypass
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Try to check permission, catch exception if permission doesn't exist
        try {
            return $user->hasPermissionTo($permission);
        } catch (\Exception $e) {
            // Permission doesn't exist in database yet
            // Deny access for non-admins
            return false;
        }
    }
}
