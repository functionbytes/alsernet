<?php

namespace Modules\Auth\Http\Controllers;

use App\Events\Auth\Password\ForgotPasswordCreated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    private const MAX_DAILY_TRIES = 3;

    public function showLinkRequest(): View
    {
        return view('auth::passwords.email');
    }

    public function sendResetLinkEmail(Request $request): View|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $request->email)
            ->orWhere('identification', $request->email)
            ->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'El correo o cédula no coincide con nuestros registros.']);
        }

        if ($this->hasTooManyResetAttempts($user)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['tried' => 'Ya lo has intentado 3 veces hoy. Comuníquese con el administrador para restablecer la contraseña.']);
        }

        $passwordToken = Str::random(50);

        $user->forceFill([
            'password_reset_token' => Hash::make($passwordToken),
            'password_reset_max_tries' => $this->nextTryCount($user),
            'password_reset_last_tried_on' => now(),
        ])->save();

        ForgotPasswordCreated::dispatch($user, $passwordToken);

        return view('auth::passwords.success', ['email' => $user->email]);
    }

    /**
     * Check if the user has exceeded the daily reset attempt limit.
     */
    private function hasTooManyResetAttempts(User $user): bool
    {
        if (empty($user->password_reset_last_tried_on)) {
            return false;
        }

        $lastTried = date('Y-m-d', strtotime($user->password_reset_last_tried_on));
        $today = date('Y-m-d');

        return $lastTried === $today && $user->password_reset_max_tries >= self::MAX_DAILY_TRIES;
    }

    /**
     * Calculate the next try count (reset counter on a new day).
     */
    private function nextTryCount(User $user): int
    {
        if (empty($user->password_reset_last_tried_on)) {
            return 1;
        }

        $lastTried = date('Y-m-d', strtotime($user->password_reset_last_tried_on));
        $today = date('Y-m-d');

        if ($lastTried !== $today) {
            return 1;
        }

        return ($user->password_reset_max_tries ?? 0) + 1;
    }

    public function username(): string
    {
        return 'email';
    }
}
