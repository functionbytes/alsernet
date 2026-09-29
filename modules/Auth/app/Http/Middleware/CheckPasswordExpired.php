<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Auth\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force users with expired passwords to change them.
 *
 * Triggered when `password_changed_at` is older than `expires_in_days`
 * from auth-policy config, or when `must_change_password` flag is set.
 *
 * 29-sep-2026: se registra en el grupo `web` (AuthServiceProvider) para que
 * cubra TODO el panel autenticado, no solo /panel/settings/auth. Solo actúa
 * en rutas con middleware `auth`, deja pasar las rutas necesarias para
 * cambiar la contraseña, salir, desbloquear la pantalla o terminar una
 * impersonación (sin bucles), y no actúa durante una impersonación (el
 * impersonador no puede cambiar la contraseña del otro usuario).
 */
class CheckPasswordExpired
{
    /** Rutas permitidas aunque haya que cambiar la contraseña. */
    public const ALLOWED_ROUTES = [
        'settings.auth.password.edit',
        'settings.auth.password.update',
        'auth.logout',
        'auth.lock',
        'auth.lock.lock',
        'auth.lock.unlock',
        'auth.impersonation.stop',
        'two-factor.challenge',
        'two-factor.verify',
    ];

    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::isAuthenticatedRoute($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $this->needsPasswordChange($user)) {
            return $next($request);
        }

        if (self::isAllowedRoute($request, self::ALLOWED_ROUTES) || $this->impersonation->isImpersonating($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Debes cambiar tu contraseña antes de continuar.',
                'redirect' => route('settings.auth.password.edit'),
            ], 423);
        }

        return redirect()->route('settings.auth.password.edit')
            ->with('warning', 'Por seguridad, debes cambiar tu contraseña antes de continuar.');
    }

    /**
     * true si la ruta exige login (middleware `auth` o `auth:<guard>`).
     */
    public static function isAuthenticatedRoute(Request $request): bool
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if ($middleware === 'auth' || str_starts_with($middleware, 'auth:')
                || $middleware === Authenticate::class || str_starts_with($middleware, Authenticate::class.':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $names
     */
    public static function isAllowedRoute(Request $request, array $names): bool
    {
        // GET /logout no tiene nombre de ruta.
        return in_array($request->route()?->getName(), $names, true) || $request->is('logout');
    }

    private function needsPasswordChange(mixed $user): bool
    {
        if ($user->must_change_password ?? false) {
            return true;
        }

        $days = (int) config('auth.auth-policy.password.expires_in_days', 0);

        if ($days <= 0) {
            return false;
        }

        $changedAt = $user->password_changed_at;

        if (! $changedAt) {
            return false;
        }

        return $changedAt->copy()->addDays($days)->isPast();
    }
}
