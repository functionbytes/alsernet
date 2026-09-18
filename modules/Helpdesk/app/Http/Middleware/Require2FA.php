<?php

namespace Modules\Helpdesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Helpdesk\Services\Compliance\TwoFactorService;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Never gate the 2FA setup/challenge/verify routes themselves, otherwise
        // the user could never reach the screen that lets them pass the check.
        if ($request->routeIs('manager.helpdesk.2fa.*')) {
            return $next($request);
        }

        $requireForAll = config('helpdesk.require_2fa', false);
        $requireForAdmins = config('helpdesk.require_2fa_for_admins', true);

        $mustChallenge = match (true) {
            $requireForAll => true,
            $requireForAdmins && $user->hasRole(['admin', 'super-admin']) => true,
            default => false,
        };

        if (! $mustChallenge) {
            return $next($request);
        }

        // 2FA is required but not yet enabled for this user
        if (! $this->twoFactorService->isEnabled($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '2FA obligatorio. Configure su autenticador en /2fa/setup.',
                    'redirect' => route('manager.helpdesk.2fa.setup'),
                ], 403);
            }

            return redirect()->route('manager.helpdesk.2fa.setup')
                ->with('warning', 'Debe activar la autenticación de dos factores para continuar.');
        }

        // 2FA is enabled but not yet verified this session
        if (! $request->session()->get('2fa_passed')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere verificación 2FA.',
                    'redirect' => route('manager.helpdesk.2fa.challenge'),
                ], 403);
            }

            return redirect()->route('manager.helpdesk.2fa.challenge')
                ->with('intended', $request->fullUrl());
        }

        return $next($request);
    }
}
