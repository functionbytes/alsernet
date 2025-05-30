<?php

namespace App\Http\Controllers\Managers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $searchKey = $request->search;
        $roles = Role::withCount('users')->with('permissions');

        if ($searchKey) {
            $roles->where('name', 'like', '%' . $searchKey . '%');
        }

        $roles = $roles->paginate(paginationNumber());

        return view('managers.views.roles.roles.index')->with([
            'roles' => $roles,
            'searchKey' => $searchKey,
        ]);
    }

    public function create()
    {
        return view('managers.views.roles.roles.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50|unique:roles,name',
            'guard_name' => 'required|in:web,api',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        Role::create($request->only('name', 'guard_name'));

        return response()->json(['success' => true, 'message' => 'Rol creado correctamente.']);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        return view('managers.views.roles.roles.edit')->with([
            'role' => $role,
        ]);

    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50|unique:roles,name,' . $request->id,
            'guard_name' => 'required|in:web,api',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $role = Role::findOrFail($request->id);
        $role->update($request->only('name', 'guard_name'));

        return response()->json(['success' => true, 'message' => 'Rol actualizado correctamente.']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['super-admin', 'customer'])) {
            return response()->json(['success' => false, 'message' => 'Este rol del sistema no puede ser eliminado.']);
        }

        $role->delete();
        return redirect()->route('manager.roles');
    }

    public function permissions($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('managers.views.roles.roles.permissions')->with([
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);


    }

    public function updatePermissions(Request $request)
    {

        $role = Role::find($request->id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado.'
            ], 404);
        }

        if (!is_array($request->permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'El campo permissions debe ser un array de IDs.'
            ], 400);
        }

        $permissions = Permission::whereIn('id', $request->permissions)
            ->where('guard_name', $role->guard_name)
            ->get();

        $role->syncPermissions($permissions);

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente.',
            'assigned' => $permissions->pluck('name')
        ]);


    }



}


