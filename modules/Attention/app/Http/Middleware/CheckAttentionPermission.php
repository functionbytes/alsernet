<?php

namespace Modules\Attention\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Attention\Models\Attention;
use Symfony\Component\HttpFoundation\Response;

class CheckAttentionPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Get the attention instance from route parameter
        $attention = $this->getAttentionFromRoute($request);

        // If no attention found in route, check if this is a general permission
        if (! $attention) {
            // For permissions that don't require an attention instance (like viewAny, create)
            if (in_array($permission, ['viewAny', 'create', 'viewAll'])) {
                if (! $request->user()->can($permission, Attention::class)) {
                    abort(403, 'No tienes permiso para realizar esta acción.');
                }

                return $next($request);
            }

            abort(404, 'PQRSF no encontrado.');
        }

        // Check the permission
        if (! $request->user()->can($permission, $attention)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }

    /**
     * Get attention instance from route parameters.
     */
    protected function getAttentionFromRoute(Request $request): ?Attention
    {
        // Try to get from 'attention' parameter
        $attention = $request->route('attention');

        if ($attention instanceof Attention) {
            return $attention;
        }

        // Try to get from 'uid' parameter
        $uid = $request->route('uid');

        if ($uid) {
            return Attention::where('uid', $uid)->firstOrFail();
        }

        // Try to get from 'id' parameter
        $id = $request->route('id');

        if ($id) {
            return Attention::findOrFail($id);
        }

        return null;
    }
}
