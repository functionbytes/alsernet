<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;

class SettingsController extends Controller
{
    /**
     * Display the settings dashboard/index page.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.manage');

        return view('mailing::settings.index');
    }
}
