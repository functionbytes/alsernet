<?php

namespace Modules\Role\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Role\Services\ActivePermissionService;
use Modules\Role\Services\PrivilegeGuard;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function __construct(
        private readonly ActivePermissionService $activePermissionService,
        private readonly PrivilegeGuard $guard,
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
        // getPermissionsViaRoles() no deduplica entre roles: si dos roles del
        // usuario comparten un permiso (p. ej. super-admin ya lo tiene todo y
        // el segundo rol repite un subconjunto), el mismo id sale más de una
        // vez. Para in_array() da igual, pero un count() sobre esto sin
        // unique() mentiría.
        $rolePermissions = $user->getPermissionsViaRoles()->pluck('id')->unique()->values()->toArray();

        return view('role::users.permissions', compact('user', 'permissions', 'userPermissions', 'rolePermissions'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'action' => ['required', 'in:attach,detach'],
        ]);

        $permission = Permission::findById($request->integer('permission_id'));
        $isAttach = $request->input('action') === 'attach';

        // 29-sep-2026: anti-escalada (usuarios privilegiados y permisos que el actor no tiene).
        $this->assertAllowed($user, $isAttach ? [$permission] : []);

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

        $current = $user->permissions()->pluck('name')->all();
        $this->assertAllowed($user, $permissions->reject(fn (Permission $p) => in_array($p->name, $current, true)));

        $user->syncPermissions($permissions);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['count' => $permissions->count()])
            ->log("Permisos directos sincronizados para el usuario {$user->email}");

        return $this->success('Permisos sincronizados correctamente.');
    }

    private function assertAllowed(User $target, iterable $grantedPermissions): void
    {
        $actor = auth()->user();

        abort_unless(
            $this->guard->canManageUser($actor, $target) && $this->guard->canGrantPermissions($actor, $grantedPermissions),
            403,
            'No tienes privilegios suficientes para asignar estos permisos.'
        );
    }
}
