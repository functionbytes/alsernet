<?php

namespace Modules\Pulse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Laravel\Pulse\Facades\Pulse;

class PulseController extends Controller
{
    /**
     * Display the Pulse dashboard
     */
    public function index(): View
    {
        // Return the Pulse dashboard view
        // Pulse is configured to handle the dashboard rendering
        return view('pulse::dashboard');
    }
}
