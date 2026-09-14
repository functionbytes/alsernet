<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Events\Password\ResetPasswordCreated;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Rules\PasswordNotReused;
use Modules\Auth\Rules\StrongPassword;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth::auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): View|RedirectResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => array_filter([
                'required',
                'string',
                'confirmed',
                new StrongPassword,
                $user ? new PasswordNotReused($user) : null,
            ]),
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if (! $user) {
            return back()->withErrors(['password' => 'No se encontró una cuenta con ese correo electrónico.']);
        }

        if (! $user->password_reset_token || ! Hash::check($request->token, $user->password_reset_token)) {
            return back()->withErrors(['password' => 'El enlace de restablecimiento es inválido o ha expirado.']);
        }

        if ($this->isTokenExpired($user)) {
            $user->forceFill([
                'password_reset_token' => null,
                'password_reset_max_tries' => null,
                'password_reset_last_tried_on' => null,
            ])->save();

            return back()->withErrors(['password' => 'El enlace de restablecimiento ha expirado. Solicita uno nuevo.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
            'password_reset_token' => null,
            'password_reset_max_tries' => null,
            'password_reset_last_tried_on' => null,
        ])->save();

        $user->sessions()->delete();

        PasswordChanged::dispatch($user, $request->ip(), 'reset');
        ResetPasswordCreated::dispatch($user);

        return view('auth::auth.passwords.confirm', ['email' => $user->email]);
    }

    private function isTokenExpired(User $user): bool
    {
        if (empty($user->password_reset_last_tried_on)) {
            return true;
        }

        $hours = (int) config('auth.auth-policy.reset_token.expiry_hours', 48);
        $issuedAt = strtotime($user->password_reset_last_tried_on);
        $expiresAt = $issuedAt + ($hours * 3600);

        return time() > $expiresAt;
    }
}
