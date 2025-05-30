<?php

namespace App\Http\Controllers\Managers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $searchKey = $request->search;
        $permissions = Permission::latest();

        if ($searchKey) {
            $permissions->where('name', 'like', '%' . $searchKey . '%');
        }

        $permissions = $permissions->paginate(paginationNumber());

        return view('managers.views.roles.permissions.index')->with([
            'permissions' => $permissions,
            'searchKey' => $searchKey,
        ]);
    }

    public function create()
    {
        return view('managers.views.roles.permissions.create');
    }

    public function store(Request $request)
    {
        $permission = Permission::create([
            'name' => Str::lower($request->name),
            'guard_name' => Str::lower($request->guard_name),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permiso creado correctamente.',
        ]);
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return view('managers.views.roles.permissions.edit')->with([
            'permission' => $permission,
        ]);
    }

    public function update(Request $request)
    {
        $permission = Permission::findOrFail($request->id);

        $permission->update([
            'name' => Str::lower($request->name),
            'guard_name' => Str::lower($request->guard_name),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permiso actualizado correctamente.',
        ]);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return redirect()->route('manager.permissions');
    }
}
