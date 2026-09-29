<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Events\ImpersonationEnded;
use Modules\Auth\Events\ImpersonationStarted;
use Modules\Auth\Models\ImpersonationLog;
use Modules\Role\Services\PrivilegeGuard;
use RuntimeException;

/**
 * Handles start/stop of user impersonation with audit trail.
 * Stores the impersonator id in session so `stop()` can restore it.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'auth.impersonator_id';

    public const SESSION_LOG_KEY = 'auth.impersonation_log_id';

    public const SESSION_STARTED_KEY = 'auth.impersonation_started_at';

    public function isEnabled(): bool
    {
        return (bool) config('auth.auth-policy.impersonation.enabled', true);
    }

    public function isImpersonating(Request $request): bool
    {
        return $request->session()->has(self::SESSION_KEY);
    }

    public function canImpersonate(User $user, User $target): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        $permission = (string) config('auth.auth-policy.impersonation.required_permission', 'auth.impersonate');

        if (! $user->can($permission)) {
            return false;
        }

        // 29-sep-2026: jerarquía — solo un super-admin impersona a un usuario
        // con rol privilegiado (super-admin, super-settings, settings).
        return app(PrivilegeGuard::class)->canManageUser($user, $target);
    }

    /**
     * true si la impersonación activa superó max_duration_minutes (29-sep-2026).
     */
    public function hasExpired(Request $request): bool
    {
        $startedAt = (int) $request->session()->get(self::SESSION_STARTED_KEY, 0);
        $max = (int) config('auth.auth-policy.impersonation.max_duration_minutes', 60);

        if (! $this->isImpersonating($request) || $max <= 0) {
            return false;
        }

        // Sesiones impersonadas anteriores al cambio (sin marca de inicio): se cortan.
        return $startedAt === 0 || (time() - $startedAt) > $max * 60;
    }

    public function start(User $impersonator, User $target, Request $request, ?string $reason = null): void
    {
        if (! $this->canImpersonate($impersonator, $target)) {
            throw new RuntimeException('No autorizado para impersonar este usuario.');
        }

        // Sin impersonaciones anidadas (el "stop" volvería al usuario intermedio).
        if ($this->isImpersonating($request)) {
            throw new RuntimeException('Ya estás impersonando a otro usuario. Termina esa sesión primero.');
        }

        $log = ImpersonationLog::create([
            'impersonator_id' => $impersonator->id,
            'impersonated_id' => $target->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => $reason,
            'started_at' => now(),
        ]);

        Auth::login($target);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $impersonator->id);
        $request->session()->put(self::SESSION_LOG_KEY, $log->id);
        $request->session()->put(self::SESSION_STARTED_KEY, time());

        ImpersonationStarted::dispatch($impersonator, $target, $request->ip());
    }

    public function stop(Request $request): ?User
    {
        $impersonatorId = $request->session()->pull(self::SESSION_KEY);
        $logId = $request->session()->pull(self::SESSION_LOG_KEY);
        $request->session()->forget(self::SESSION_STARTED_KEY);

        if (! $impersonatorId) {
            return null;
        }

        /** @var User|null $impersonator */
        $impersonator = User::find($impersonatorId);
        $impersonated = Auth::user();

        if ($logId) {
            ImpersonationLog::where('id', $logId)->update(['ended_at' => now()]);
        }

        if ($impersonator) {
            Auth::login($impersonator);
            $request->session()->regenerate();

            if ($impersonated) {
                ImpersonationEnded::dispatch($impersonator, $impersonated);
            }
        } else {
            // El impersonador ya no existe: no dejar la sesión abierta como el usuario objetivo.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $impersonator;
    }

    public function impersonatorId(Request $request): ?int
    {
        return $request->session()->get(self::SESSION_KEY);
    }
}
