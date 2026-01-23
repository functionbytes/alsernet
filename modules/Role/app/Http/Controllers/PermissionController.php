<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', $this->getPaginationPerPage());

        $permissions = Permission::query()
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return $this->success('Permissions retrieved successfully', [
                'permissions' => $permissions,
            ]);
        }

        return view('role::permissions.index', compact('permissions', 'search'));
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        return view('role::permissions.create');
    }

    /**
     * Store a newly created permission in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'unique:permissions,name',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'guard_name' => [
                'required',
                'string',
                'in:web,api',
            ],
        ], [
            'name.required' => 'El nombre del permiso es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'name.unique' => 'Este nombre de permiso ya existe.',
            'name.regex' => 'El nombre solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'guard_name.required' => 'El guard es requerido.',
            'guard_name.in' => 'El guard debe ser "web" o "api".',
        ]);

        $permission = Permission::create([
            'name' => Str::lower($validated['name']),
            'guard_name' => Str::lower($validated['guard_name']),
        ]);

        if ($request->expectsJson()) {
            return $this->success('Permiso creado correctamente.', [
                'permission' => $permission,
            ]);
        }

        return redirect()->route('settings.permissions.index')
            ->with('success', 'Permiso creado correctamente.');
    }

    /**
     * Show the form for editing the specified permission
     */
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return view('role::permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission in storage
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'unique:permissions,name,'.$permission->id,
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'guard_name' => [
                'required',
                'string',
                'in:web,api',
            ],
        ], [
            'name.required' => 'El nombre del permiso es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'name.unique' => 'Este nombre de permiso ya existe.',
            'name.regex' => 'El nombre solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'guard_name.required' => 'El guard es requerido.',
            'guard_name.in' => 'El guard debe ser "web" o "api".',
        ]);

        $permission->update([
            'name' => Str::lower($validated['name']),
            'guard_name' => Str::lower($validated['guard_name']),
        ]);

        if ($request->expectsJson()) {
            return $this->success('Permiso actualizado correctamente.', [
                'permission' => $permission,
            ]);
        }

        return redirect()->route('settings.permissions.edit', $permission->id)
            ->with('success', 'Permiso actualizado correctamente.');
    }

    /**
     * Remove the specified permission from storage
     */
    public function destroy(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        // Check if permission is assigned to any roles
        if (Role::whereHas('permissions', function ($query) use ($permission) {
            $query->where('permissions.id', $permission->id);
        })->exists()) {
            if ($request->expectsJson()) {
                return $this->error('No se puede eliminar un permiso que está asignado a roles.');
            }

            return back()->with('error', 'No se puede eliminar un permiso que está asignado a roles.');
        }

        $permission->delete();

        if ($request->expectsJson()) {
            return $this->success('Permiso eliminado correctamente.');
        }

        return redirect()->route('settings.permissions.index')
            ->with('success', 'Permiso eliminado correctamente.');
    }
}
