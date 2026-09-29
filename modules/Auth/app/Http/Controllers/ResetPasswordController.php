<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Events\Password\ResetPasswordCreated;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Rules\PasswordNotReused;
use Modules\Auth\Rules\StrongPassword;

/**
 * 29-sep-2026: usa el broker nativo (tabla password_reset_tokens). Mensaje de
 * error genérico (antes "No se encontró una cuenta con ese correo" permitía
 * enumerar) y la ruta POST lleva throttle.
 */
class ResetPasswordController extends Controller
{
    private const INVALID_LINK = 'El enlace de restablecimiento no es válido o ha caducado. Solicita uno nuevo.';

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth::auth.passwords.reset', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): View|RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $broker = Password::broker();

        /** @var User|null $user */
        $user = $broker->getUser(['email' => (string) $request->input('email')]);

        if (! $user || ! $broker->tokenExists($user, (string) $request->input('token'))) {
            return back()->withErrors(['password' => self::INVALID_LINK]);
        }

        // El historial solo se comprueba con el token ya validado (no filtra si la cuenta existe).
        $history = Validator::make($request->only('password'), [
            'password' => [new PasswordNotReused($user)],
        ]);

        if ($history->fails()) {
            return back()->withErrors($history);
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
            'failed_login_count' => 0,
            'locked_until' => null,
        ])->save();

        $broker->deleteToken($user);
        $user->sessions()->delete();

        PasswordChanged::dispatch($user, $request->ip(), 'reset');
        ResetPasswordCreated::dispatch($user);

        return view('auth::auth.passwords.confirm', ['email' => $user->email]);
    }
}
