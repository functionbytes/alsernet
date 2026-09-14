<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block sensitive routes (password change, 2FA setup, etc.) when an admin
 * is impersonating another user. Prevents an impersonator from altering
 * the target account's credentials.
 */
class DenyWhenImpersonating
{
    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->isImpersonating($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta acción no está permitida mientras impersonas a otro usuario.',
                ], 403);
            }

            abort(403, 'Acción no permitida durante impersonación.');
        }

        return $next($request);
    }
}
