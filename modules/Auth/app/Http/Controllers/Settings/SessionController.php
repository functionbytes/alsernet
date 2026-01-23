<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    /**
     * Display active sessions
     */
    public function index(Request $request): View
    {
        // TODO: Implement session tracking
        $sessions = [];

        return view('auth::settings.sessions.index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Revoke a specific session
     */
    public function destroy(Request $request, string $sessionId)
    {
        // TODO: Implement session revocation

        return redirect()->route('settings.auth.sessions.index')
            ->with('success', 'Sesión revocada correctamente');
    }
}
