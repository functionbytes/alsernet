<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use ThrottlesLogins;

    /**
     * The maximum number of login attempts
     */
    protected int $maxAttempts = 5;

    /**
     * The number of minutes to throttle for
     */
    protected int $decayMinutes = 1;

    /**
     * Show the login form or redirect to dashboard if authenticated
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Redirect if already authenticated
        if (Auth::check()) {
            return redirect()->route(Auth::user()->redirectRouteName());
        }

        return view('auth::auth.login');
    }

    /**
     * Handle root route - redirect based on authentication status
     */
    public function home(): RedirectResponse
    {
        // If authenticated, redirect to user's dashboard
        if (Auth::check()) {
            return redirect()->route(Auth::user()->redirectRouteName());
        }

        // If not authenticated, show login form
        return redirect()->route('auth.login');
    }

    /**
     * Handle login attempt (supports both JSON and form submission)
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $this->validateLogin($request);

        // Check if too many login attempts
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Attempt login
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // Increment login attempts
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the login request
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application
     */
    protected function attemptLogin(Request $request): bool
    {
        return Auth::attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    /**
     * Get the login credentials from the request
     */
    protected function credentials(Request $request): array
    {
        return [
            $this->username() => $request->input($this->username()),
            'password' => $request->input('password'),
        ];
    }

    /**
     * Send the response after the user was authenticated
     */
    protected function sendLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        $user = Auth::user();

        // Check if user account is available/enabled
        if (! $user->available) {
            Auth::logout();
            $request->session()->invalidate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está deshabilitada. Contacta al administrador.',
                ], 403);
            }

            return back()->withErrors([
                'email' => 'Tu cuenta está deshabilitada. Contacta al administrador.',
            ])->onlyInput('email');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'redirect' => route($user->redirectRouteName()),
            ]);
        }

        return redirect()->intended(route($user->redirectRouteName()));
    }

    /**
     * Send the failed login response
     */
    protected function sendFailedLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        $user = User::where('email', $request->input($this->username()))->first();

        if (! $user) {
            $message = 'El correo no coincide con nuestros registros.';
        } elseif (! Hash::check($request->input('password'), $user->password)) {
            $message = 'La contraseña ingresada no es válida.';
        } else {
            $message = 'Las credenciales proporcionadas no coinciden con nuestros registros.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        throw ValidationException::withMessages([
            $this->username() => [$message],
        ]);
    }

    /**
     * Send the lockout response
     */
    protected function sendLockoutResponse(Request $request): JsonResponse|RedirectResponse
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        $message = 'Demasiados intentos de inicio de sesión. Inténtelo de nuevo en '.$seconds.' segundos.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 429);
        }

        throw ValidationException::withMessages([
            $this->username() => [$message],
        ])->status(429);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    /**
     * Get the login username to be used by the controller
     */
    public function username(): string
    {
        return 'email';
    }
}
