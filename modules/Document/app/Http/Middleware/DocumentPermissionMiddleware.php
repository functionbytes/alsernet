<?php

namespace Modules\Document\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // Map route names to required permissions
        $permissionMap = [
            'documents.index' => 'view-all-documents',
            'documents.pending' => 'view-documents',
            'documents.show' => 'view-documents',
            'documents.summary' => 'view-documents',
            'documents.manage' => 'document-management',
            'documents.destroy' => 'delete-documents',
            'documents.import' => 'import-documents',
            'documents.import.api' => 'sync-api',
            'documents.import.erp' => 'sync-erp',
        ];

        // Get current route name
        $routeName = $request->route()?->getName();

        // Check if we have a specific permission parameter from the route
        if ($permission) {
            $requiredPermission = $permission;
        } else {
            // Look up permission from route name
            $requiredPermission = $permissionMap[$routeName] ?? null;
        }

        // Verify user has the required permission
        if ($requiredPermission && ! auth()->user()?->canDocument($requiredPermission)) {
            return response()->view('documents::documents.access-denied', [
                'action' => $requiredPermission,
                'message' => 'No tienes permiso para acceder a esta sección.',
            ], 403);
        }

        return $next($request);
    }
}
