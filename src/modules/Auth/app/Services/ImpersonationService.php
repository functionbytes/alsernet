<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Events\ImpersonationEnded;
use Modules\Auth\Events\ImpersonationStarted;
use Modules\Auth\Models\ImpersonationLog;
use RuntimeException;

/**
 * Handles start/stop of user impersonation with audit trail.
 * Stores the impersonator id in session so `stop()` can restore it.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'auth.impersonator_id';

    public const SESSION_LOG_KEY = 'auth.impersonation_log_id';

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

        return $user->can($permission);
    }

    public function start(User $impersonator, User $target, Request $request, ?string $reason = null): void
    {
        if (! $this->canImpersonate($impersonator, $target)) {
            throw new RuntimeException('No autorizado para impersonar este usuario.');
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
        $request->session()->put(self::SESSION_KEY, $impersonator->id);
        $request->session()->put(self::SESSION_LOG_KEY, $log->id);

        ImpersonationStarted::dispatch($impersonator, $target, $request->ip());
    }

    public function stop(Request $request): ?User
    {
        $impersonatorId = $request->session()->pull(self::SESSION_KEY);
        $logId = $request->session()->pull(self::SESSION_LOG_KEY);

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

            if ($impersonated) {
                ImpersonationEnded::dispatch($impersonator, $impersonated);
            }
        }

        return $impersonator;
    }

    public function impersonatorId(Request $request): ?int
    {
        return $request->session()->get(self::SESSION_KEY);
    }
}
