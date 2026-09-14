<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class DemoController extends Controller
{
    /**
     * Demo / install instructions page for tenants.
     * GET /pages/helpdesk-widget/{websiteToken?}
     */
    public function widget(Request $request, ?string $websiteToken = null): View
    {
        $web = $websiteToken ? Web::where('website_token', $websiteToken)->first() : null;
        $config = $web?->getWidgetConfig() ?? [];

        return view('helpdesklivechat::public.widget.demo', [
            'websiteToken' => $websiteToken,
            'webWidget' => $web,
            'config' => $config,
        ]);
    }
}
