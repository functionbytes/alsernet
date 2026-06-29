<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force users with expired passwords to change them.
 *
 * Triggered when `password_changed_at` is older than `expires_in_days`
 * from auth-policy config, or when `must_change_password` flag is set.
 */
class CheckPasswordExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $this->needsPasswordChange($user)) {
            return $next($request);
        }

        $allowed = [
            'settings.auth.password.edit',
            'settings.auth.password.update',
            'auth.logout',
        ];

        if (in_array($request->route()?->getName(), $allowed, true)) {
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

        return $changedAt->addDays($days)->isPast();
    }
}
