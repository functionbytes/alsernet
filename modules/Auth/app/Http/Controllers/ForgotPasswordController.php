<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Notifications\ResetPasswordNotification;
use Modules\Auth\Services\AuthRateLimiter;

/**
 * 29-sep-2026: sustituido el flujo casero (escribía columnas que no existen en
 * `users`, daba 500 si la cuenta existía y 200 si no → enumeración, y nunca
 * enviaba el correo) por el broker nativo (tabla password_reset_tokens, token
 * con caducidad y throttle). La respuesta es idéntica exista o no la cuenta y
 * ya no se busca por `identification` ni se muestra el email de la cuenta.
 */
class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly AuthRateLimiter $limiter,
    ) {}

    public function showLinkRequest(): View
    {
        return view('auth::auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request): View|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Introduce un correo electrónico válido.',
        ]);

        $email = (string) $request->input('email');
        $check = $this->limiter->check('password_reset', $email, $request);

        if (! $check['allowed']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Demasiados intentos. Inténtalo de nuevo en {$check['seconds']} segundos."]);
        }

        $this->limiter->hit('password_reset', $email, $request);

        // El estado (enviado, usuario inexistente, throttled) se ignora a propósito.
        $status = Password::broker()->sendResetLink(['email' => $email], function ($user, string $token) {
            if ($user->available) {
                $user->notify(new ResetPasswordNotification($token));
            }
        });

        // Igualar el coste del bcrypt del token para no delatar la cuenta por tiempo.
        if ($status === Password::INVALID_USER) {
            Hash::make(Str::random(40));
        }

        return view('auth::auth.passwords.success', ['email' => $email]);
    }

    public function username(): string
    {
        return 'email';
    }
}
