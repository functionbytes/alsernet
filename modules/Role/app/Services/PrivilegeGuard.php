<?php

namespace Modules\Role\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reglas anti-escalada de privilegios (29-sep-2026).
 *
 * Antes, quien tuviera roles.edit / edit-users / users.permissions.assign
 * podía asignarse super-admin, darse cualquier permiso directo o cambiar la
 * contraseña de un super-admin. Reglas:
 *  - Los roles de config('role.privileged_roles') solo los gestiona un super-admin.
 *  - Un usuario con uno de esos roles solo lo edita/borra/impersona un super-admin.
 *  - Quien no es super-admin solo concede permisos que ya tiene, y solo asigna
 *    roles cuyos permisos sean un subconjunto de los suyos.
 */
class PrivilegeGuard
{
    public const SUPER_ADMIN = 'super-admin';

    /** @var array<int, array<int, string>> */
    private array $permissionCache = [];

    public function privilegedRoles(): array
    {
        return (array) config('role.privileged_roles', [self::SUPER_ADMIN, 'super-settings', 'settings']);
    }

    public function isSuperAdmin(?User $actor): bool
    {
        return $actor !== null && $actor->hasRole(self::SUPER_ADMIN);
    }

    public function isPrivilegedRole(Role|string $role): bool
    {
        $name = $role instanceof Role ? $role->name : $role;

        return in_array($name, $this->privilegedRoles(), true);
    }

    public function isPrivilegedUser(User $user): bool
    {
        return $user->hasAnyRole($this->privilegedRoles());
    }

    /** Cambiar permisos, usuarios o datos de un rol. */
    public function canManageRole(?User $actor, Role $role): bool
    {
        return $this->isSuperAdmin($actor) || ! $this->isPrivilegedRole($role);
    }

    /** Asignar un rol a usuarios (propios o ajenos). */
    public function canAssignRole(?User $actor, Role|string $role): bool
    {
        if ($this->isSuperAdmin($actor)) {
            return true;
        }

        if ($actor === null) {
            return false;
        }

        $role = $role instanceof Role ? $role : Role::where('name', $role)->where('guard_name', 'web')->first();

        if ($role === null || $this->isPrivilegedRole($role)) {
            return false;
        }

        return $this->canGrantPermissions($actor, $role->permissions);
    }

    /**
     * @param  iterable<Permission|string>  $permissions
     */
    public function canGrantPermissions(?User $actor, iterable $permissions): bool
    {
        if ($this->isSuperAdmin($actor)) {
            return true;
        }

        if ($actor === null) {
            return false;
        }

        $own = $this->actorPermissionNames($actor);

        foreach ($permissions as $permission) {
            $name = $permission instanceof Permission ? $permission->name : (string) $permission;

            if (! in_array($name, $own, true)) {
                return false;
            }
        }

        return true;
    }

    /** Editar, borrar, cambiar roles/permisos o impersonar a un usuario. */
    public function canManageUser(?User $actor, User $target): bool
    {
        return $this->isSuperAdmin($actor) || ! $this->isPrivilegedUser($target);
    }

    /**
     * Roles que el actor puede asignar (para filtrar selects).
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(?User $actor): Collection
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        if ($this->isSuperAdmin($actor)) {
            return $roles;
        }

        return $roles->filter(fn (Role $role) => $this->canAssignRole($actor, $role))->values();
    }

    /**
     * @return array<int, string>
     */
    private function actorPermissionNames(User $actor): array
    {
        return $this->permissionCache[$actor->id] ??= $actor->getAllPermissions()->pluck('name')->all();
    }
}
