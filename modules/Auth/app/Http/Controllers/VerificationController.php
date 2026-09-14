<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['verify']);
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only(['verify', 'resend']);
    }

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->redirectRouteName());
        }

        return view('auth::auth.verify');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->redirectRouteName())
                ->with('verified', true);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route($request->user()->redirectRouteName())
            ->with('verified', true);
    }

    public function resend(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu correo ya está verificado.']);
            }

            return redirect()->route($request->user()->redirectRouteName());
        }

        $request->user()->sendEmailVerificationNotification();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Enlace de verificación enviado.']);
        }

        return back()->with('resent', true);
    }
}
