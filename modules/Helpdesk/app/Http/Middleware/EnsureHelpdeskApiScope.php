<?php

namespace Modules\Helpdesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies least-privilege scopes to Sanctum tokens used by Helpdesk APIs.
 * Session-authenticated requests and Sanctum's transient test token continue
 * through the normal controller/policy authorization path.
 */
class EnsureHelpdeskApiScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || $token->can('*')) {
            return $next($request);
        }

        $required = $request->isMethodSafe() ? 'helpdesk.read' : 'helpdesk.write';
        $allowed = $token->can($required) || $token->can('helpdesk.manage');

        if (! $allowed) {
            return response()->json([
                'message' => 'El token no tiene permisos suficientes para esta operación.',
                'required_scope' => $required,
            ], 403);
        }

        return $next($request);
    }
}
