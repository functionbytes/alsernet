<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Events\LoginFailed;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Events\UserLoggedOut;

class AuthService
{
    public function __construct(
        private readonly DeviceService $devices,
    ) {}

    /**
     * Attempt login and return the user on success, null on failure.
     * Applies account-level lockout (persistent, cross-device).
     */
    public function attempt(array $credentials, bool $remember, Request $request): ?User
    {
        $user = User::where('email', $credentials['email'] ?? null)->first();

        if ($user && $user->isLocked()) {
            LoginFailed::dispatch(
                $user->email,
                $request->ip(),
                $request->userAgent(),
                $user,
                'account_locked',
            );

            return null;
        }

        if (! $user || ! Hash::check($credentials['password'] ?? '', $user->password)) {
            if ($user) {
                $this->registerFailure($user);
            }

            LoginFailed::dispatch(
                $credentials['email'] ?? null,
                $request->ip(),
                $request->userAgent(),
                $user,
            );

            return null;
        }

        if (! $user->available) {
            LoginFailed::dispatch(
                $user->email,
                $request->ip(),
                $request->userAgent(),
                $user,
                'account_disabled',
            );

            return null;
        }

        Auth::login($user, $remember);
        $this->resetFailureCount($user);

        return $user;
    }

    /**
     * Finalize a login session. Dispatches UserLoggedIn and registers device.
     */
    public function completeLogin(User $user, Request $request, bool $remember = false): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->devices->registerForUser($user, $request);

        UserLoggedIn::dispatch($user, $request->ip(), $request->userAgent(), $remember);
    }

    public function logout(Request $request): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            UserLoggedOut::dispatch($user, $request->ip());
        }
    }

    /**
     * Revoke all session and API tokens for a user (admin force-logout).
     */
    public function forceLogoutAll(User $user): void
    {
        $user->sessions()->delete();
        $user->tokens()->delete();
    }

    /**
     * Increment failure counter and apply account lockout if threshold reached.
     */
    private function registerFailure(User $user): void
    {
        if (! config('auth.auth-policy.lockout.enabled', true)) {
            return;
        }

        $max = (int) config('auth.auth-policy.lockout.max_attempts', 10);
        $duration = (int) config('auth.auth-policy.lockout.duration_minutes', 30);

        $count = ((int) $user->failed_login_count) + 1;
        $update = ['failed_login_count' => $count];

        if ($count >= $max) {
            $update['locked_until'] = now()->addMinutes($duration);
            $update['failed_login_count'] = 0;
        }

        $user->forceFill($update)->save();
    }

    private function resetFailureCount(User $user): void
    {
        if ($user->failed_login_count === 0 && $user->locked_until === null) {
            return;
        }

        $user->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
        ])->save();
    }
}
