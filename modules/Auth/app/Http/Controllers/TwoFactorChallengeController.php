<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Modules\Auth\Services\TwoFactorService;

class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly TwoFactorService $twoFactor
    ) {}

    /**
     * Show the 2FA challenge form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('auth.login');
        }

        return view('auth::auth.two-factor-challenge');
    }

    /**
     * Verify the submitted OTP or recovery code.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión expirada. Inicia sesión nuevamente.'], 401);
            }

            return redirect()->route('auth.login');
        }

        $throttleKey = '2fa:'.$userId.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Demasiados intentos. Inténtalo de nuevo en {$seconds} segundos.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429);
            }

            return back()->withErrors(['code' => $message]);
        }

        $user = User::findOrFail($userId);

        if ($this->isValidOtp($request, $user) || $this->isValidRecoveryCode($request, $user)) {
            RateLimiter::clear($throttleKey);

            $request->session()->forget('two_factor_user_id');
            $request->session()->put('two_factor_passed', true);

            Auth::login($user);
            $request->session()->regenerate();

            $redirect = route($user->redirectRouteName());

            if ($request->expectsJson()) {
                return response()->json(['redirect' => $redirect]);
            }

            return redirect()->intended($redirect);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        $message = 'Código incorrecto. Inténtalo de nuevo.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['code' => $message]);
    }

    private function isValidOtp(Request $request, User $user): bool
    {
        $code = $request->input('code', '');

        return $code && $this->twoFactor->verify($user->two_factor_secret, $code);
    }

    private function isValidRecoveryCode(Request $request, User $user): bool
    {
        $recovery = trim($request->input('recovery_code', ''));

        if (! $recovery || ! $user->two_factor_recovery_codes) {
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
