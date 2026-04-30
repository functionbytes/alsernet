<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('Chat::settings.dashboard');
    }
}
