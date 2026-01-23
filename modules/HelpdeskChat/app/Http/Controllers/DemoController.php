<?php

namespace Modules\HelpdeskChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChat\Models\Channels\Web;

class DemoController extends Controller
{
    /**
     * Show widget demo page.
     *
     * GET /demo/widget/{websiteToken?}
     */
    public function widget(Request $request, ?string $websiteToken = null): View
    {
        $webWidget = null;
        $config = null;

        // If websiteToken provided, load that widget's settings
        if ($websiteToken) {
            $webWidget = Web::where('website_token', $websiteToken)->first();

            if ($webWidget) {
                $config = $webWidget->getWidgetConfig();
            }
        }

        // Default config for generic demo
        if (! $config) {
            $config = [
                'websiteToken' => 'demo',
                'baseUrl' => url('/'),
                'widgetColor' => '#1f93ff',
                'widgetPosition' => 'right',
                'welcomeTitle' => 'Hello! 👋',
                'welcomeTagline' => 'How can we help you today?',
                'preChatFormEnabled' => false,
                'showPoweredBy' => true,
            ];
        }

        return view('helpdeskChat::demo.widget', [
            'websiteToken' => $websiteToken,
            'webWidget' => $webWidget,
            'config' => $config,
        ]);
    }
}
