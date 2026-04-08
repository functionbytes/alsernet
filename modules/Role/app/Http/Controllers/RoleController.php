<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Role\Events\RoleCreated;
use Modules\Role\Events\RoleDeleted;
use Modules\Role\Events\RoleUpdated;
use Modules\Role\Events\UserRoleChanged;
use Modules\Role\Http\Requests\Systems\RoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->get('per_page', $this->getPaginationPerPage());
        $searchKey = $request->get('search', '');

        $roles = Role::withCount('users')
            ->when($searchKey, function ($query) use ($searchKey) {
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('name', 'like', "%{$searchKey}%")
                        ->orWhere('description', 'like', "%{$searchKey}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return $this->success('Roles retrieved successfully', [
                'roles' => $roles,
            ]);
        }

        return view('role::roles.index', compact('roles', 'searchKey'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create(): View
    {
        return view('role::roles.create');
    }

    /**
     * Store a newly created role in storage
     */
    public function store(RoleRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $role = Role::create($data);

        // Assign permissions if provided
        if ($request->has('permissions') && is_array($request->input('permissions'))) {
            $permissions = Permission::whereIn('id', $request->input('permissions'))
                ->where('guard_name', $role->guard_name)
                ->get();

            $role->syncPermissions($permissions);
        }

        event(new RoleCreated($role, auth()->user()));

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['name' => $role->name])
            ->log('Role created');

        if ($request->expectsJson()) {
            return $this->success('Role created successfully', [
                'role' => $role->load('permissions'),
            ]);
        }

        return redirect()->route('settings.roles.edit', $role->id)
            ->with('success', 'Rol creado correctamente.');
    }

    /**
     * Display the specified role
     */
    public function show(Role $role): View|JsonResponse
    {
        $role->load('users', 'permissions');

        if (request()->expectsJson()) {
            return $this->success('Role retrieved successfully', [
                'role' => $role,
            ]);
        }

        return view('role::roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role): View
    {
        $permissions = $this->getAvailablePermissions();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('role::roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage
     */
    public function update(RoleRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // Prevent system roles from being modified
        if (in_array($role->name, config('role.protected_roles', []), true)) {
            return $this->error('Cannot modify system roles');
        }

        $role->update($data);

        // Update permissions if provided
        if ($request->has('permissions') && is_array($request->input('permissions'))) {
            $permissions = Permission::whereIn('id', $request->input('permissions'))
                ->where('guard_name', $role->guard_name)
                ->get();

            $role->syncPermissions($permissions);
        }

        event(new RoleUpdated($role, auth()->user()));

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['name' => $role->name])
            ->log('Role updated');

        if ($request->expectsJson()) {
            return $this->success('Role updated successfully', [
                'role' => $role->load('permissions'),
            ]);
        }

        return redirect()->route('settings.roles.edit', $role->id)
            ->with('success', 'Rol actualizado correctamente.');
    }

    /**
     * Remove the specified role from storage
     */
    public function destroy(Role $role): RedirectResponse|JsonResponse
    {
        // Prevent system roles from being deleted
        if (in_array($role->name, config('role.protected_roles', []), true)) {
            if (request()->expectsJson()) {
                return $this->error('Cannot delete system roles');
            }

            return back()->with('error', 'No se pueden eliminar roles del sistema.');
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            if (request()->expectsJson()) {
                return $this->error('Cannot delete role with assigned users');
            }

            return back()->with('error', 'No se puede eliminar un rol que tiene usuarios asignados.');
        }

        event(new RoleDeleted($role, auth()->user()));

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['name' => $role->name, 'id' => $role->id])
            ->log('Role deleted');

        $role->delete();

        if (request()->expectsJson()) {
            return $this->success('Role deleted successfully');
        }

        return redirect()->route('settings.roles.index')
            ->with('success', 'Rol eliminado correctamente.');
    }

    /**
     * Duplicate a role (API response)
     */
    public function duplicate(Role $role): JsonResponse
    {
        $newRole = $role->replicate();
        $newRole->name = $role->name.' (Copy)';
        $newRole->save();

        $newRole->syncPermissions($role->permissions);

        return $this->success('Role duplicated successfully', [
            'role' => $newRole->load('permissions'),
        ]);
    }

    /**
     * Clone a role and redirect to its edit page
     */
    public function clone(Role $role): RedirectResponse
    {
        $newRole = $role->replicate();
        $newRole->name = $role->name.'_copia_'.now()->format('His');
        $newRole->save();

        $newRole->syncPermissions($role->permissions);

        return redirect()->route('settings.roles.edit', $newRole->id)
            ->with('success', 'Rol clonado correctamente. Puedes renombrarlo y ajustar permisos.');
    }

    /**
     * Assign users to a role
     */
    public function assignUsers(Role $role, Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids', []);

        $users = User::whereIn('id', $userIds)->get();
        $users->each(fn ($user) => $user->roles()->syncWithoutDetaching([$role->id]));

        $actor = auth()->user();
        $users->each(fn ($user) => event(new UserRoleChanged($user, $role, 'assigned', $actor)));

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['user_ids' => $userIds, 'count' => $users->count()])
            ->log('Users assigned to role');

        return $this->success("{$users->count()} users assigned to role successfully", [
            'assigned_count' => $users->count(),
        ]);
    }

    /**
     * Remove a user from a role
     */
    public function removeUser(Role $role, User $user): JsonResponse
    {
        $user->roles()->detach($role->id);

        event(new UserRoleChanged($user, $role, 'removed', auth()->user()));

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $user->id])
            ->log('User removed from role');

        return $this->success('User removed from role successfully', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Show permissions assignment form
     */
    public function showPermissions(Role $role): View
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('role::roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update permissions for a role
     */
    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        // Handle individual permission toggle (from permission matrix)
        if ($request->has('permission_id') && $request->has('action')) {
            $permissionId = $request->input('permission_id');
            $action = $request->input('action');

            $permission = Permission::findOrFail($permissionId);

            if ($action === 'attach') {
                $role->givePermissionTo($permission);
            } elseif ($action === 'detach') {
                $role->revokePermissionTo($permission);
            }

            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties(['permission' => $permission->name, 'action' => $action])
                ->log('Role permission toggled');

            return $this->success('Permiso actualizado correctamente', [
                'permission' => $permission->name,
                'action' => $action,
                'role' => $role->name,
            ]);
        }

        // Handle bulk permissions update (from permissions page)
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->input('permissions'))
            ->where('guard_name', $role->guard_name)
            ->get();

        $role->syncPermissions($permissions);

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['permissions' => $permissions->pluck('name')])
            ->log('Role permissions updated');

        return $this->success('Permisos actualizados correctamente', [
            'assigned' => $permissions->pluck('name'),
        ]);
    }

    /**
     * Show users assigned to a role
     */
    public function showUsers(Role $role, Request $request): View|JsonResponse
    {
        $search = $request->get('search', '');

        $users = $role->users()
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate($this->getPaginationPerPage());

        if ($request->expectsJson()) {
            return $this->success('Users retrieved successfully', [
                'users' => $users,
            ]);
        }

        return view('role::roles.users', compact('role', 'users', 'search'));
    }

    /**
     * Show modules that can be viewed by a role
     */
    public function showModules(Role $role): View
    {
        // Lista de módulos disponibles (debe coincidir con ModulePermissionsSeeder)
        $modules = [
            'documents' => 'Documentos',
            'mailers' => 'Emails',
            'media' => 'Gestor de Medios',
            'users' => 'Usuarios',
            'events' => 'Eventos',
            'warehouse' => 'Almacén',
            'webhooks' => 'Webhooks',
            'roles' => 'Roles y Permisos',
            'auth' => 'Seguridad',
            'notifications' => 'Notificaciones',
            'backups' => 'Copias de seguridad',
            'settings' => 'Configuraciones',
            'helpdesk' => 'Helpdesk',
            'campaigns' => 'Campañas',
            'suppliers' => 'Proveedores',
            'analytics' => 'Analytics',
        ];

        // Obtener los módulos que actualmente tiene permiso este rol
        $roleModules = $role->permissions()
            ->where('name', 'like', 'modules.view.%')
            ->pluck('name')
            ->map(function ($name) {
                return str_replace('modules.view.', '', $name);
            })
            ->toArray();

        return view('role::roles.modules', compact('role', 'modules', 'roleModules'));
    }

    /**
     * Update modules that can be viewed by a role
     */
    public function updateModules(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'modules' => 'array',
            'modules.*' => 'string',
        ]);

        $modules = $request->input('modules', []);

        // Obtener todos los permisos de módulos para este rol
        $modulePermissions = Permission::where('guard_name', $role->guard_name)
            ->where('name', 'like', 'modules.view.%')
            ->get();

        // Sincronizar: solo mantener los permisos de módulos seleccionados
        $permissionsToSync = [];
        foreach ($modules as $moduleId) {
            $permission = $modulePermissions->firstWhere('name', "modules.view.{$moduleId}");
            if ($permission) {
                $permissionsToSync[] = $permission->id;
            }
        }

        // Wrap detach+attach in a transaction to avoid race conditions
        DB::transaction(function () use ($role, $modulePermissions, $permissionsToSync) {
            $role->permissions()
                ->where('guard_name', $role->guard_name)
                ->whereIn('id', $modulePermissions->pluck('id'))
                ->detach();

            if (! empty($permissionsToSync)) {
                $role->permissions()->attach($permissionsToSync);
            }
        });

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['modules' => $modules])
            ->log('Role modules updated');

        return $this->success('Módulos actualizados correctamente', [
            'modules' => $modules,
        ]);
    }

    /**
     * Show permission matrix for all roles
     */
    public function showPermissionMatrix(): View
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        $permissions = Permission::orderBy('name')->get();

        $permissionsByModule = $permissions->groupBy(function (Permission $permission) {
            return explode('.', $permission->name)[0] ?? 'other';
        })->map(fn ($modulePerms) => $modulePerms->groupBy(function (Permission $permission) {
            return explode('.', $permission->name)[1] ?? 'general';
        }));

        $rolePermissions = $roles->mapWithKeys(
            fn (Role $role) => [$role->id => $role->permissions->pluck('id')->toArray()]
        )->toArray();

        return view('role::roles.matrix', compact(
            'roles',
            'permissions',
            'permissionsByModule',
            'rolePermissions'
        ));
    }

    public function bulkRemoveUsers(Role $role, Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:200'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $role->users()->detach($request->user_ids);

        $count = count($request->user_ids);

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "{$count} usuario(s) removido(s) del rol.",
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $systemRoles = ['super-settings', 'customer'];

        $roles = Role::whereIn('id', $request->ids)
            ->whereNotIn('name', $systemRoles)
            ->get();

        $skipped = count($request->ids) - $roles->count();

        foreach ($roles as $role) {
            event(new RoleDeleted($role, auth()->user()));
            $role->delete();
        }

        $msg = $roles->count().' rol(es) eliminado(s).';
        if ($skipped) {
            $msg .= " {$skipped} omitido(s) por ser roles del sistema.";
        }

        return response()->json(['success' => true, 'count' => $roles->count(), 'message' => $msg]);
    }
}
