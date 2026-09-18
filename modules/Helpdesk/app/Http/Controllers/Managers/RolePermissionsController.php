<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Role\Services\ActivePermissionService;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Adaptador JSON para el modal "Permisos del rol" (#75 ve-role-perms) del inbox.
 *
 * El motor real de roles/permisos vive en Modules\Role (panel/settings/roles/*,
 * ver RoleController::showPermissions/updatePermissions) pero solo expone una
 * vista HTML completa y trabaja con IDs de Permission — el modal del inbox
 * quiere una matriz reducida (view/create/update/delete por módulo activo) en
 * JSON. Este controller traduce entre ambos formatos reutilizando el mismo
 * ActivePermissionService, sin duplicar la lógica de autorización de roles.
 */
class RolePermissionsController extends Controller
{
    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    public function __construct(
        private readonly ActivePermissionService $activePermissionService,
    ) {}

    /**
     * GET /panel/helpdesk/roles — lista simple para el selector de rol del
     * modal cuando se abre sin contexto (desde "Más opciones" del inbox).
     */
    public function index(): JsonResponse
    {
        $this->authorize('roles.permissions.view');

        return response()->json([
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * GET /panel/helpdesk/roles/{role}/permissions
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('roles.permissions.view');

        $activeModuleSlugs = collect(Module::all())
            ->filter(fn ($m) => $m->isEnabled())
            ->map(fn ($m) => strtolower($m->getName()))
            ->values();

        $matrixPermissions = $this->activePermissionService->getActivePermissions()
            ->filter(fn (Permission $p) => $this->isMatrixPermissionName($p->name, $activeModuleSlugs));

        $modules = $matrixPermissions
            ->map(fn (Permission $p) => explode('.', $p->name)[0])
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $slug) => [
                'name' => $slug,
                'label' => Str::ucfirst(str_replace(['_', '-'], ' ', $slug)),
            ]);

        $rolePermissionNames = $role->permissions->pluck('name');

        $permissions = $matrixPermissions
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [$name => $rolePermissionNames->contains($name)]);

        return response()->json([
            'modules' => $modules->values(),
            'permissions' => $permissions,
        ]);
    }

    /**
     * POST /panel/helpdesk/roles/{role}/permissions (X-HTTP-Method-Override: PUT)
     *
     * Sincroniza SOLO el subconjunto {módulo activo}.{view|create|update|delete}
     * que la matriz del modal puede mostrar — nunca un syncPermissions() global,
     * para no borrar permisos del rol que caigan fuera de ese patrón (p. ej.
     * "helpdesk.manage" o "roles.edit") y que este modal ni siquiera muestra.
     *
     * El checkbox "Aplicar a todos los agentes con este rol" del modal no tiene
     * contraparte real: los permisos son del ROL, así que ya se aplican a todos
     * los agentes que lo tengan — se ignora su valor.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('roles.permissions.manage');

        $activeModuleSlugs = collect(Module::all())
            ->filter(fn ($m) => $m->isEnabled())
            ->map(fn ($m) => strtolower($m->getName()))
            ->values();

        $matrixPermissionNames = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->get()
            ->filter(fn (Permission $p) => $this->isMatrixPermissionName($p->name, $activeModuleSlugs))
            ->pluck('name');

        $checked = collect($request->input('permissions', []))
            ->filter()
            ->keys();

        $toGrant = $matrixPermissionNames->intersect($checked)->values();
        $toRevoke = $matrixPermissionNames->diff($checked)->values();

        if ($toGrant->isNotEmpty()) {
            $role->givePermissionTo($toGrant->all());
        }
        if ($toRevoke->isNotEmpty()) {
            $role->revokePermissionTo($toRevoke->all());
        }

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['granted' => $toGrant, 'revoked' => $toRevoke])
            ->log('Role permissions updated (inbox quick matrix)');

        return response()->json([
            'success' => true,
            'message' => 'Permisos guardados',
        ]);
    }

    private function isMatrixPermissionName(string $name, Collection $activeModuleSlugs): bool
    {
        $parts = explode('.', $name);

        return count($parts) === 2
            && in_array($parts[0], $activeModuleSlugs->all(), true)
            && in_array($parts[1], self::ACTIONS, true);
    }
}
