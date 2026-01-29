<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Automation2;
use Modules\Mailing\Models\Notification;
use Modules\Mailing\Models\SendingDomain;
use Modules\Mailing\Models\Subscriber;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Trigger admin monitoring events when admin is logged in
        event(new \Modules\Mailing\Events\AdminLoggedIn);
    }

    /**
     * Show the application admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! config('app.saas')) {
            return redirect()->action('HomeController@index');
        }

        $currentTimezone = $request->user()->admin->getTimezone();

        $notifications = Notification::top();

        return view('admin.dashboard', [
            'notifications' => $notifications,
            'subscribersCount' => Subscriber::count(),
            'automationsCount' => Automation2::count(),
            'sendingDomainsCount' => SendingDomain::count(),
            'currentTimezone' => $currentTimezone,
        ]);
    }
}
