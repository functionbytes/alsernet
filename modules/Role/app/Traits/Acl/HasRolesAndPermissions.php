<?php

namespace Modules\Role\Traits\Acl;

use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Trait HasRolesAndPermissions
 *
 * Combines Spatie Permission traits for role and permission management.
 * Provides methods for checking, assigning, and revoking roles and permissions.
 */
trait HasRolesAndPermissions
{
    use HasPermissions;
    use HasRoles;
}
