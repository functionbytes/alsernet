<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Role\Http\Requests\Systems\RoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request)
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
    public function create()
    {
        return view('role::roles.create');
    }

    /**
     * Store a newly created role in storage
     */
    public function store(RoleRequest $request)
    {
        $data = $request->validated();

        // Track who created this role
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $role = Role::create($data);

        // Assign permissions if provided
        if ($request->has('permissions') && is_array($request->input('permissions'))) {
            $permissions = Permission::whereIn('id', $request->input('permissions'))
                ->where('guard_name', $role->guard_name)
                ->get();

            $role->syncPermissions($permissions);
        }

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
    public function show(Role $role)
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
    public function edit(Role $role)
    {
        $permissions = $this->getAvailablePermissions();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('role::roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage
     */
    public function update(RoleRequest $request, Role $role)
    {
        $data = $request->validated();

        // Prevent system roles from being modified
        if (in_array($role->name, ['super-admin', 'customer'])) {
            return $this->error('Cannot modify system roles');
        }

        // Track who updated this role
        $data['updated_by'] = auth()->id();

        $role->update($data);

        // Update permissions if provided
        if ($request->has('permissions') && is_array($request->input('permissions'))) {
            $permissions = Permission::whereIn('id', $request->input('permissions'))
                ->where('guard_name', $role->guard_name)
                ->get();

            $role->syncPermissions($permissions);
        }

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
    public function destroy(Role $role)
    {
        // Prevent system roles from being deleted
        if (in_array($role->name, ['super-admin', 'customer'])) {
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

        $role->delete();

        if (request()->expectsJson()) {
            return $this->success('Role deleted successfully');
        }

        return redirect()->route('settings.roles.index')
            ->with('success', 'Rol eliminado correctamente.');
    }

    /**
     * Duplicate a role
     */
    public function duplicate(Role $role)
    {
        $newRole = $role->replicate();
        $newRole->name = $role->name.' (Copy)';
        $newRole->created_by = auth()->id();
        $newRole->updated_by = auth()->id();
        $newRole->save();

        // Copy permissions
        $newRole->syncPermissions($role->permissions);

        return $this->success('Role duplicated successfully', [
            'role' => $newRole->load('permissions'),
        ]);
    }

    /**
     * Assign users to a role
     */
    public function assignUsers(Role $role, Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids', []);
        $assignedCount = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->roles()->syncWithoutDetaching([$role->id]);
                $assignedCount++;
            }
        }

        return $this->success("$assignedCount users assigned to role successfully", [
            'assigned_count' => $assignedCount,
        ]);
    }

    /**
     * Remove a user from a role
     */
    public function removeUser(Role $role, User $user)
    {
        $user->roles()->detach($role->id);

        return $this->success('User removed from role successfully', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Show permissions assignment form
     */
    public function showPermissions(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('role::roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update permissions for a role
     */
    public function updatePermissions(Request $request, Role $role)
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

        return $this->success('Permisos actualizados correctamente', [
            'assigned' => $permissions->pluck('name'),
        ]);
    }

    /**
     * Show users assigned to a role
     */
    public function showUsers(Role $role, Request $request)
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
    public function showModules(Role $role)
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
    public function updateModules(Request $request, Role $role)
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

        // Primero, remover todos los permisos de módulos actuales
        $role->permissions()
            ->where('guard_name', $role->guard_name)
            ->whereIn('id', $modulePermissions->pluck('id'))
            ->detach();

        // Luego, asignar los nuevos permisos de módulos
        if (! empty($permissionsToSync)) {
            $role->permissions()->attach($permissionsToSync);
        }

        return $this->success('Módulos actualizados correctamente', [
            'modules' => $modules,
        ]);
    }

    /**
     * Show permission matrix for all roles
     */
    public function showPermissionMatrix()
    {
        // Get all roles
        $roles = Role::orderBy('name')->get();

        // Get all permissions grouped by module and then by category
        $permissions = Permission::orderBy('name')->get();

        // Group permissions by module, then by category/action
        $permissionsByModule = $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($modulePermissions) {
            return $modulePermissions->groupBy(function ($permission) {
                $parts = explode('.', $permission->name);

                return $parts[1] ?? 'general';
            });
        });

        // Get role permissions mapped by role_id => [permission_ids]
        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->id] = $role->permissions()->pluck('id')->toArray();
        }

        return view('role::roles.matrix', compact(
            'roles',
            'permissions',
            'permissionsByModule',
            'rolePermissions'
        ));
    }
}
