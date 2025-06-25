<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class CheckRolesAndPermissions
{
    public function handle(Request $request, Closure $next, $roleType = null)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $roleMapping = [
            'manager'       => ['super-admin', 'admin', 'manager'],
            'callcenter'    => ['super-admin', 'admin', 'callcenter-manager', 'callcenter-agent'],
            'inventarie'    => ['super-admin', 'admin', 'inventory-manager', 'inventory-staff'],
            'shop'          => ['super-admin', 'admin', 'shop-manager', 'shop-staff'],
            'administrative'=> ['super-admin', 'admin', 'administrative'],
        ];

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        if ($roleType && isset($roleMapping[$roleType])) {
            if (!$user->hasAnyRole($roleMapping[$roleType])) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }
        }

        $this->checkSpecificPermissions($request, $user,$roleType);

        return $next($request);
    }




    private function checkSpecificPermissions(Request $request, $user, string $role): void
    {
        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return;
        }

        // Grupo → Roles permitidos
        $roleMapping = [
            'manager'        => ['super-admin', 'admin', 'manager'],
            'callcenter'     => ['super-admin', 'admin', 'callcenter-manager', 'callcenter-agent'],
            'inventarie'     => ['super-admin', 'admin', 'inventory-manager', 'inventory-staff'],
            'shop'           => ['super-admin', 'admin', 'shop-manager', 'shop-staff'],
            'administrative' => ['super-admin', 'admin', 'administrative'],
        ];

        if (!isset($roleMapping[$role])) {
            abort(403, "Grupo no válido: $role");
        }

        $validRoles = $roleMapping[$role];

        // Verificar que el usuario tenga algún rol válido para este grupo
        $userRoleNames = $user->roles->pluck('name')->toArray();
        $hasAllowedRole = collect($userRoleNames)->intersect($validRoles)->isNotEmpty();

        if (!$hasAllowedRole) {
            abort(403, "Tu rol no pertenece al grupo permitido para esta ruta ($role).");
        }

        // Extraer permisos válidos solo desde los roles permitidos
        $permissions = $user->roles
            ->filter(fn($r) => in_array($r->name, $validRoles))
            ->flatMap(fn($r) => $r->permissions->pluck('name'))
            ->unique()
            ->values()
            ->toArray();

        // Quitar el prefijo del grupo (ej: callcenter.) → returns.index
        $internalRoute = str($routeName)->after("{$role}.")->toString();
        $segments = explode('.', $internalRoute);

        if (count($segments) < 2) {
            abort(403, "Ruta no válida: $routeName");
        }

        $resource = $segments[0]; // ej: returns
        $action = $segments[1];   // ej: index

        // Mapeo de acción a sufijo de permiso
        $actionToPermission = [
            'index'      => 'view',
            'show'       => 'view',
            'pdf'        => 'view',
            'payments'   => 'view',
            'create'     => 'create',
            'store'      => 'create',
            'edit'       => 'update',
            'update'     => 'update',
            'bulk'       => 'update',
            'destroy'    => 'delete',
            'status'     => 'status.update',
            'approve'    => 'status.approve',
            'reject'     => 'status.reject',
            'assign'     => 'assign',
            'discussion' => 'discussion.add',
            'attachment' => 'attachment.upload',
            'payment'    => 'payment.add',
            'export'     => 'export',
        ];

        // Resolver el nombre del permiso
        $suffix = $actionToPermission[$action] ?? $action;
        $permission = "{$resource}.{$suffix}";

        if (!in_array($permission, $permissions)) {
            abort(403, "No tienes permisos para acceder a esta ruta: $permission");
        }
    }








}
