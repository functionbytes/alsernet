<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Show 2FA backups
     */
    public function index(): View
    {
        return view('auth::settings.two-factor.index');
    }

    /**
     * Enable 2FA
     */
    public function enable(Request $request)
    {
        // TODO: Implement 2FA enable logic

        return redirect()->route('settings.auth.two-factor.index')
            ->with('success', 'Autenticación de dos factores habilitada');
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        // TODO: Implement 2FA disable logic

        return redirect()->route('settings.auth.two-factor.index')
            ->with('success', 'Autenticación de dos factores deshabilitada');
    }
}
