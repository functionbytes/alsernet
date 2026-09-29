<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta la impersonación al superar auth-policy.impersonation.max_duration_minutes
 * (29-sep-2026: el límite existía en config pero no se aplicaba) y devuelve la
 * sesión al impersonador.
 */
class EnforceImpersonationTimeout
{
    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! $this->impersonation->hasExpired($request)) {
            return $next($request);
        }

        $impersonator = $this->impersonation->stop($request);
        $message = 'La sesión de impersonación ha caducado.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route($impersonator ? $impersonator->redirectRouteName() : 'auth.login')
            ->with('warning', $message);
    }
}
