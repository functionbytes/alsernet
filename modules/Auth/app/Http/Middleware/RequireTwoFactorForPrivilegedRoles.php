<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Services\ImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA obligatorio para roles privilegiados (29-sep-2026).
 *
 * Desactivado por defecto para no bloquear a nadie hasta que se configure.
 * Para activarlo (en .env):
 *   AUTH_REQUIRE_2FA=true
 *   AUTH_REQUIRE_2FA_ROLES=super-admin,super-settings,helpdesk-admin   (valor por defecto)
 * Con eso, un usuario con alguno de esos roles y sin 2FA confirmado solo puede
 * usar la pestaña "Doble factor" de su perfil (y cambiar contraseña, salir,
 * etc.) hasta activarlo. No actúa durante una impersonación.
 */
class RequireTwoFactorForPrivilegedRoles
{
    public const ALLOWED_ROUTES = [
        'settings.auth.two-factor',
        'settings.auth.two-factor.setup',
        'settings.auth.two-factor.confirm',
        'settings.auth.two-factor.recovery-codes',
        'settings.auth.two-factor.recovery-codes.pdf',
        ...CheckPasswordExpired::ALLOWED_ROUTES,
    ];

    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.auth-policy.two_factor_enforcement.enabled', false)
            || ! CheckPasswordExpired::isAuthenticatedRoute($request)) {
            return $next($request);
        }

        $user = $request->user();
        $roles = array_values(array_filter((array) config('auth.auth-policy.two_factor_enforcement.roles', [])));

        if (! $user || $roles === [] || ! method_exists($user, 'hasTwoFactorEnabled')
            || $user->hasTwoFactorEnabled() || ! $user->hasAnyRole($roles)) {
            return $next($request);
        }

        if (CheckPasswordExpired::isAllowedRoute($request, self::ALLOWED_ROUTES) || $this->impersonation->isImpersonating($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Debes activar la verificación en dos pasos antes de continuar.',
                'redirect' => route('settings.auth.two-factor'),
            ], 423);
        }

        return redirect()->route('settings.auth.two-factor')
            ->with('warning', 'Por seguridad, tu rol exige activar la verificación en dos pasos antes de continuar.');
    }
}
