<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Auth\Events\TwoFactorFailed;
use Modules\Auth\Events\TwoFactorVerified;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\TwoFactorService;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuthRateLimiter $limiter,
        private readonly AuthService $auth,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('auth.login');
        }

        return view('auth::auth.two-factor-challenge');
    }

    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión expirada. Inicia sesión nuevamente.'], 401);
            }

            return redirect()->route('auth.login');
        }

        $check = $this->limiter->check('two_factor', (string) $userId, $request);

        if (! $check['allowed']) {
            $message = "Demasiados intentos. Inténtalo de nuevo en {$check['seconds']} segundos.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429);
            }

            return back()->withErrors(['code' => $message]);
        }

        /** @var User $user */
        $user = User::findOrFail($userId);

        $method = null;

        if ($this->isValidOtp($request, $user)) {
            $method = 'otp';
        } elseif ($this->isValidRecoveryCode($request, $user)) {
            $method = 'recovery_code';
        }

        if ($method !== null) {
            $this->limiter->clear('two_factor', (string) $userId, $request);

            $remember = (bool) $request->session()->pull('two_factor_remember', false);
            $request->session()->forget('two_factor_user_id');
            $request->session()->put('two_factor_passed', true);

            Auth::login($user, $remember);
            $request->session()->regenerate();

            TwoFactorVerified::dispatch($user, $request->ip(), $method);
            $this->auth->completeLogin($user, $request, $remember);

            $redirect = $user->must_change_password
                ? route('settings.auth.password.edit')
                : route($user->redirectRouteName());

            if ($request->expectsJson()) {
                return response()->json(['redirect' => $redirect]);
            }

            return redirect()->intended($redirect);
        }

        $this->limiter->hit('two_factor', (string) $userId, $request);
        TwoFactorFailed::dispatch($user, $request->ip());

        $message = 'Código incorrecto. Inténtalo de nuevo.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['code' => $message]);
    }

    private function isValidOtp(Request $request, User $user): bool
    {
        $code = (string) $request->input('code', '');

        return $code !== '' && $this->twoFactor->verify($user->two_factor_secret, $code);
    }

    private function isValidRecoveryCode(Request $request, User $user): bool
    {
        $recovery = trim((string) $request->input('recovery_code', ''));

        if ($recovery === '' || ! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = $user->two_factor_recovery_codes;
        $index = array_search($recovery, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }
}
