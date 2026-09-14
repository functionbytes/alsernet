<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Models\MagicLoginToken;
use Modules\Auth\Notifications\MagicLinkNotification;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Auth\Services\AuthService;

class MagicLinkController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuthRateLimiter $limiter,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (! config('auth.auth-policy.magic_link.enabled', true)) {
            abort(404);
        }

        if (Auth::check()) {
            return redirect()->route(Auth::user()->redirectRouteName());
        }

        return view('auth::auth.magic-link');
    }

    public function request(Request $request): RedirectResponse
    {
        abort_unless(config('auth.auth-policy.magic_link.enabled', true), 404);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = (string) $request->input('email');

        $check = $this->limiter->check('password_reset', $email, $request);
        if (! $check['allowed']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Demasiados intentos. Reintenta en {$check['seconds']} segundos."]);
        }

        $this->limiter->hit('password_reset', $email, $request);

        $user = User::where('email', $email)->first();

        if ($user && $user->available && ! $user->isLocked()) {
            $ttl = (int) config('auth.auth-policy.magic_link.expires_in_minutes', 15);
            $plainToken = Str::random(48);

            MagicLoginToken::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainToken),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'expires_at' => now()->addMinutes($ttl),
                'created_at' => now(),
            ]);

            $url = route('auth.magic-link.consume', ['token' => $plainToken]);
            $user->notify(new MagicLinkNotification($url, $ttl));
        }

        return back()->with('status', 'Si tu correo existe, recibirás un enlace de inicio de sesión en breve.');
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        abort_unless(config('auth.auth-policy.magic_link.enabled', true), 404);

        $record = MagicLoginToken::where('token_hash', hash('sha256', $token))->first();

        if (! $record || ! $record->isValid()) {
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'El enlace es inválido o ha expirado.']);
        }

        $record->forceFill(['used_at' => now()])->save();

        $user = $record->user;

        if (! $user || ! $user->available) {
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'La cuenta no está disponible.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('two_factor_user_id', $user->id);

            return redirect()->route('two-factor.challenge');
        }

        $this->auth->completeLogin($user, $request);

        return redirect()->intended(route($user->redirectRouteName()));
    }
}
