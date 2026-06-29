<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Events\Password\ForgotPasswordCreated;
use Modules\Auth\Services\AuthRateLimiter;

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
            'email' => ['required', 'string', 'max:255'],
        ]);

        $identifier = (string) $request->input('email');
        $check = $this->limiter->check('password_reset', $identifier, $request);

        if (! $check['allowed']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Demasiados intentos. Inténtalo de nuevo en {$check['seconds']} segundos."]);
        }

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('identification', $identifier)
            ->first();

        $this->limiter->hit('password_reset', $identifier, $request);

        if (! $user) {
            // Return neutral success view to avoid email enumeration.
            return view('auth::auth.passwords.success', ['email' => $identifier]);
        }

        $passwordToken = Str::random(50);

        $user->forceFill([
            'password_reset_token' => Hash::make($passwordToken),
            'password_reset_max_tries' => 1,
            'password_reset_last_tried_on' => now(),
        ])->save();

        ForgotPasswordCreated::dispatch($user, $passwordToken);

        return view('auth::auth.passwords.success', ['email' => $user->email]);
    }

    public function username(): string
    {
        return 'email';
    }
}
