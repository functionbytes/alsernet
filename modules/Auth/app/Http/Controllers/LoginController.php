<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Auth\Services\AuthService;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuthRateLimiter $limiter,
    ) {}

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->redirectRouteName());
        }

        return view('auth::auth.login');
    }

    public function home(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->redirectRouteName());
        }

        return redirect()->route('auth.login');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = (string) $request->input('email');
        $check = $this->limiter->check('login', $identifier, $request);

        if (! $check['allowed']) {
            return $this->lockoutResponse($request, $check['seconds']);
        }

        $user = $this->auth->attempt(
            ['email' => $identifier, 'password' => $request->input('password')],
            $request->boolean('remember'),
            $request,
        );

        if (! $user) {
            $this->limiter->hit('login', $identifier, $request);

            $existing = User::where('email', $identifier)->first();
            if ($existing && $existing->isLocked()) {
                return $this->lockedAccountResponse($request, $existing);
            }

            return $this->failedResponse($request);
        }

        $this->limiter->clear('login', $identifier, $request);
        $request->session()->regenerate();

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('two_factor_user_id', $user->id);
            $request->session()->put('two_factor_remember', $request->boolean('remember'));

            $challengeUrl = route('two-factor.challenge');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'two_factor' => true,
                    'redirect' => $challengeUrl,
                ]);
            }

            return redirect($challengeUrl);
        }

        $this->auth->completeLogin($user, $request, $request->boolean('remember'));

        if ($user->must_change_password) {
            $redirect = route('settings.auth.password.edit');
        } else {
            $redirect = route($user->redirectRouteName());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'redirect' => $redirect,
            ]);
        }

        return redirect()->intended($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()->route('auth.login');
    }

    private function failedResponse(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Las credenciales proporcionadas no son correctas.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        throw ValidationException::withMessages(['email' => [$message]]);
    }

    private function lockoutResponse(Request $request, int $seconds): JsonResponse|RedirectResponse
    {
        $message = "Demasiados intentos de inicio de sesión. Inténtelo de nuevo en {$seconds} segundos.";

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 429);
        }

        throw ValidationException::withMessages(['email' => [$message]])->status(429);
    }

    private function lockedAccountResponse(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $minutes = (int) ceil(now()->diffInSeconds($user->locked_until, false) / 60);
        $message = "Tu cuenta está bloqueada por seguridad. Inténtalo de nuevo en {$minutes} minuto(s) o restablece la contraseña.";

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message, 'locked' => true], 423);
        }

        throw ValidationException::withMessages(['email' => [$message]])->status(423);
    }
}
