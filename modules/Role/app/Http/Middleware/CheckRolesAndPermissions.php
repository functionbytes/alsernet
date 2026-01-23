<?php

namespace Modules\Role\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRolesAndPermissions
{
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
            return redirect()->route('auth.login');
        }

        // Super-admin has access to everything
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // If roleType is specified, check if user has that role
        if ($roleType && ! $user->hasRole($roleType)) {
            $this->logAccessDenial($request, $user, "Rol '{$roleType}' no autorizado para acceder a esta sección");
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // Check specific permissions for the route
        $this->checkSpecificPermissions($request, $user, $roleType);

        return $next($request);
    }

    /**
     * Verify that the user has specific permissions for the requested route
     * Uses Spatie Laravel Permission to check permissions
     */
    private function checkSpecificPermissions(Request $request, $user, ?string $role): void
    {
        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return;
        }

        // Extract route segments for permission checking
        // Example: callcenter.returns.index → resource: returns, action: index
        $segments = explode('.', $routeName);

        if (count($segments) < 2) {
            $this->logAccessDenial($request, $user, "Ruta inválida: $routeName");
            abort(403, "Ruta no válida: $routeName");
        }

        $resource = $segments[count($segments) - 2]; // e.g: returns
        $action = $segments[count($segments) - 1];    // e.g: index

        // Resolve the required permission from the action
        $suffix = $this->actionToPermission[$action] ?? $action;
        $permission = "{$resource}.{$suffix}";

        // Check if user has the permission using Spatie
        if (! $user->hasPermissionTo($permission)) {
            $this->logAccessDenial($request, $user, "Permiso requerido: {$permission}");
            abort(403, "No tienes permisos para acceder a esta ruta: {$permission}");
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
