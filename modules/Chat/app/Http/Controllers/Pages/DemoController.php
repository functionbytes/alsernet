<?php

namespace Modules\Chat\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Web;

class DemoController extends Controller
{
    /**
     * Show widget pages page.
     *
     * GET /pages/widget/{websiteToken?}
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

        // Default config for generic pages
        if (! $config) {
            $config = [
                'websiteToken' => 'pages',
                'baseUrl' => url('/'),
                'widgetColor' => '#1f93ff',
                'widgetPosition' => 'right',
                'welcomeTitle' => 'Hello! 👋',
                'welcomeTagline' => 'How can we help you today?',
                'preChatFormEnabled' => false,
                'showPoweredBy' => true,
            ];
        }

        return view('Chat::pages.widget', [
            'websiteToken' => $websiteToken,
            'webWidget' => $webWidget,
            'config' => $config,
        ]);
    }
}
