<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display user profile with tabs
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Get active tab from request (default: account)
        $activeTab = $request->get('tab', 'account');

        return view('auth::settings.profile.index', [
            'user' => $user,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Display sessions tab
     */
    public function sessions(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'sessions']));
    }

    /**
     * Display two-factor authentication tab
     */
    public function twoFactor(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'two-factor']));
    }

    /**
     * Display password change tab
     */
    public function password(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'password']));
    }

    /**
     * Display devices tab
     */
    public function devices(Request $request): View
    {
        return $this->index($request->merge(['tab' => 'devices']));
    }
}
