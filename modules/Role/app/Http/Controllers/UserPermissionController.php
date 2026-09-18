<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Role\Services\ActivePermissionService;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function __construct(
        private readonly ActivePermissionService $activePermissionService,
    ) {
        $this->middleware('can:users.permissions.assign')->only(['index', 'show', 'update', 'sync']);
    }

    public function index(Request $request): View
    {
        $perPage = $request->get('per_page', $this->getPaginationPerPage());
        $search = $request->get('search', '');

        $users = User::query()
            ->withCount(['permissions as direct_permissions_count'])
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage);

        $totalWithPermissions = User::has('permissions')->count();
        $totalWithoutPermissions = User::doesntHave('permissions')->count();

        return view('role::users.index', compact(
            'users',
            'search',
            'totalWithPermissions',
            'totalWithoutPermissions'
        ));
    }

    public function show(User $user): View
    {
        $permissions = $this->activePermissionService->getActivePermissions();
        $userPermissions = $user->permissions()->pluck('id')->toArray();

        return view('role::users.permissions', compact('user', 'permissions', 'userPermissions'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'action' => ['required', 'in:attach,detach'],
        ]);

        $permission = Permission::findById($request->integer('permission_id'));
        $isAttach = $request->input('action') === 'attach';

        if ($isAttach) {
            $user->givePermissionTo($permission);
        } else {
            $user->revokePermissionTo($permission);
        }

        $verb = $isAttach ? 'asignado' : 'revocado';

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['permission' => $permission->name, 'action' => $request->input('action')])
            ->log("Permiso directo {$verb} al usuario {$user->email}");

        return $this->success("Permiso '{$permission->name}' {$verb}.");
    }

    public function sync(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissions = Permission::whereIn('id', $request->input('permissions', []))->get();

        $user->syncPermissions($permissions);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['count' => $permissions->count()])
            ->log("Permisos directos sincronizados para el usuario {$user->email}");

        return $this->success('Permisos sincronizados correctamente.');
    }
}
