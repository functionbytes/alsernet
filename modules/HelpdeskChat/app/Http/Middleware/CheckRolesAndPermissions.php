<?php

namespace Modules\HelpdeskChat\Http\Middleware;

use App\Models\Role\RoleMapping;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckRolesAndPermissions
{
    /**
     * Get role mappings dynamically from database with caching
     * Caches for 1 hour to improve performance
     */
    protected function getRoleMapping(): array
    {
        return Cache::remember('role_mappings_active', 3600, function () {
            return RoleMapping::getActive();
        });
    }

    /**
     * Mapping of HTTP actions to permission suffixes
     */
    protected array $actionToPermission = [
        'index' => 'view',
        'show' => 'view',
        'pdf' => 'view',
        'payments' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'bulk' => 'update',
        'destroy' => 'delete',
        'status' => 'status.update',
        'approve' => 'status.approve',
        'reject' => 'status.reject',
        'assign' => 'assign',
        'discussion' => 'discussion.add',
        'attachment' => 'attachment.upload',
        'payment' => 'payment.add',
        'export' => 'export',
    ];

    public function handle(Request $request, Closure $next, $roleType = null)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $roleMapping = $this->getRoleMapping();
        if ($roleType && isset($roleMapping[$roleType])) {
            if (! $user->hasAnyRole($roleMapping[$roleType])) {
                $this->logAccessDenial($request, $user, 'Rol no autorizado para acceder a esta sección');
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }
        }

        $this->checkSpecificPermissions($request, $user, $roleType);

        return $next($request);
    }

    /**
     * Verify that the user has specific permissions for the requested route
     */
    private function checkSpecificPermissions(Request $request, $user, string $role): void
    {
        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return;
        }

        $roleMapping = $this->getRoleMapping();
        if (! isset($roleMapping[$role])) {
            $this->logAccessDenial($request, $user, "Grupo no válido: $role");
            abort(403, "Grupo no válido: $role");
        }

        $validRoles = $roleMapping[$role];

        // Verify user has one of the allowed roles for this group
        $userRoleNames = $user->roles->pluck('name')->toArray();
        $hasAllowedRole = collect($userRoleNames)->intersect($validRoles)->isNotEmpty();

        if (! $hasAllowedRole) {
            $this->logAccessDenial($request, $user, "Rol no pertenece al grupo permitido: $role");
            abort(403, "Tu rol no pertenece al grupo permitido para esta ruta ($role).");
        }

        // Extract permissions only from allowed roles
        $permissions = $user->roles
            ->filter(fn ($r) => in_array($r->name, $validRoles))
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))
            ->unique()
            ->values()
            ->toArray();

        // Strip the group prefix from route name (e.g: callcenter.returns.index → returns.index)
        $internalRoute = str($routeName)->after("{$role}.")->toString();
        $segments = explode('.', $internalRoute);

        if (count($segments) < 2) {
            $this->logAccessDenial($request, $user, "Ruta inválida: $routeName");
            abort(403, "Ruta no válida: $routeName");
        }

        $resource = $segments[0]; // e.g: returns
        $action = $segments[1];   // e.g: index

        // Resolve the required permission from the action
        $suffix = $this->actionToPermission[$action] ?? $action;
        $permission = "{$resource}.{$suffix}";

        if (! in_array($permission, $permissions)) {
            $this->logAccessDenial($request, $user, "Permiso requerido: $permission");
            abort(403, "No tienes permisos para acceder a esta ruta: $permission");
        }
    }

    /**
     * Log access denial attempts for auditing purposes
     */
    private function logAccessDenial(Request $request, $user, string $reason): void
    {
        Log::warning('Access Denied', [
            'user_id' => $user->id ?? null,
            'user_email' => $user->email ?? null,
            'route_name' => $request->route()?->getName(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => $reason,
            'timestamp' => now(),
        ]);
    }
}
