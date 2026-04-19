<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\EmailChangeToken;
use Modules\Auth\Notifications\EmailChangeVerificationNotification;

class EmailChangeController extends Controller
{
    /**
     * Request an email change: validate, store pending token, email new address.
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'new_email' => ['required', 'email', 'max:255', 'different:email'],
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        $newEmail = (string) $request->input('new_email');

        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['message' => 'Ese correo ya está en uso.'], 422);
        }

        $plainToken = Str::random(48);
        $ttl = 60; // minutes

        EmailChangeToken::create([
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes($ttl),
            'created_at' => now(),
        ]);

        $url = route('auth.email-change.confirm', ['token' => $plainToken]);
        $user->notify(new EmailChangeVerificationNotification($url, $newEmail, $ttl));

        return response()->json([
            'success' => true,
            'message' => "Enviamos un enlace de confirmación a {$newEmail}. Revisa tu bandeja para completar el cambio.",
        ]);
    }

    /**
     * Consume token (public, linked from email) and apply change.
     */
    public function confirm(Request $request, string $token): RedirectResponse
    {
        $record = EmailChangeToken::where('token_hash', hash('sha256', $token))->first();

        if (! $record || ! $record->isValid()) {
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'El enlace es inválido o ha expirado.']);
        }

        $user = $record->user;

        if (! $user) {
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'Usuario no encontrado.']);
        }

        if (User::where('email', $record->new_email)->where('id', '!=', $user->id)->exists()) {
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'Ese correo ya fue registrado por otra cuenta.']);
        }

        $user->forceFill([
            'email' => $record->new_email,
            'mail_verified_at' => now(),
        ])->save();

        $record->forceFill(['confirmed_at' => now()])->save();

        return redirect()->route('auth.login')
            ->with('status', 'Correo actualizado. Inicia sesión con tu nuevo correo.');
    }
}
