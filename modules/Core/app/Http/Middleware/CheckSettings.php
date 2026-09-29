<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `settings`: acceso a las pantallas de configuración del panel
 * (/panel/settings*, /panel/setting/*, /panel/mailers…).
 *
 * 29-sep-2026: antes era un no-op y cualquier usuario logueado (callcenter,
 * helpdesk-agent, license…) llegaba a System/Supervisor/logs. Ahora solo pasan
 * los roles de administración/configuración que hoy usan esas pantallas, o
 * quien tenga el permiso directo `settings.access`. Cada controlador sigue
 * aplicando además su propio permiso fino (Forms.*, mailer.*, storage.*…).
 */
class CheckSettings
{
    /**
     * Roles con acceso al área de configuración.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'super-admin',
        'super-settings',
        'settings',
        'manager',
        'administrative',
    ];

    public const PERMISSION = 'settings.access';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! static::allows($user)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permiso para acceder a la configuración.'], 403);
            }

            abort(403, 'No tienes permiso para acceder a la configuración.');
        }

        return $next($request);
    }

    public static function allows($user): bool
    {
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(self::ROLES)) {
            return true;
        }

        // checkPermissionTo devuelve false (no lanza) si el permiso no existe.
        return method_exists($user, 'checkPermissionTo') && $user->checkPermissionTo(self::PERMISSION);
    }
}
