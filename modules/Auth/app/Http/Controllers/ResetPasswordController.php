<?php

namespace Modules\Auth\Http\Controllers;

use App\Events\Auth\Password\ResetPasswordCreated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /** Token validity window in hours */
    private const TOKEN_EXPIRY_HOURS = 48;

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth::passwords.reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): View|RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::where('email', $request->email)->first();

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
            'remember_token' => Str::random(60),
            'password_reset_token' => null,
            'password_reset_max_tries' => null,
            'password_reset_last_tried_on' => null,
        ])->save();

        $user->sessions()->delete();

        ResetPasswordCreated::dispatch($user);

        return view('auth::passwords.confirm', ['email' => $user->email]);
    }

    /**
     * Check if the reset token was issued more than TOKEN_EXPIRY_HOURS ago.
     */
    private function isTokenExpired(User $user): bool
    {
        if (empty($user->password_reset_last_tried_on)) {
            return true;
        }

        $issuedAt = strtotime($user->password_reset_last_tried_on);
        $expiresAt = $issuedAt + (self::TOKEN_EXPIRY_HOURS * 3600);

        return time() > $expiresAt;
    }
}
