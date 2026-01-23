<?php

namespace Modules\HelpdeskChat\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChat\Models\Channels\Web;

class WidgetController extends Controller
{
    /**
     * Display the widget interface (SPA entry point).
     *
     * GET /lc/widget
     * GET /lc/widget/{any}
     */
    public function index(Request $request): View
    {
        $websiteToken = $request->query('website_token');
        $config = [];

        if ($websiteToken) {
            $webWidget = Web::where('website_token', $websiteToken)->first();

            if ($webWidget) {
                $config = $webWidget->getWidgetConfig();
                $config['website_token'] = $websiteToken;
                $config['inbox_id'] = $webWidget->inbox_id;
            }
        }

        return view('helpdeskChat::widget.index', [
            'config' => $config,
            'websiteToken' => $websiteToken,
        ]);
    }

    /**
     * Display launcher demo page.
     *
     * GET /lc/launcher-demo
     * GET /livechat/launcher-demo
     */
    public function launcherDemo(Request $request): View
    {
        $demoConfigs = [
            'default' => [
                'widgetColor' => '#1f93ff',
                'widgetPosition' => 'right',
                'welcomeTitle' => 'Hello! 👋',
                'welcomeTagline' => 'How can we help you today?',
            ],
            'minimal' => [
                'widgetColor' => '#000000',
                'widgetPosition' => 'right',
                'welcomeTitle' => 'Support',
                'welcomeTagline' => 'We are here to help',
            ],
            'colorful' => [
                'widgetColor' => '#FF6B6B',
                'widgetPosition' => 'left',
                'welcomeTitle' => 'Need Help?',
                'welcomeTagline' => 'Chat with our team!',
            ],
        ];

        return view('helpdeskChat::widget.launcher-demo', [
            'demoConfigs' => $demoConfigs,
        ]);
    }

    /**
     * Get widget settings for API.
     *
     * GET /lc/api/settings
     */
    public function settings(Request $request): JsonResponse
    {
        $websiteToken = $request->query('website_token');

        if (! $websiteToken) {
            return response()->json([
                'success' => false,
                'error' => 'Website token is required',
            ], 422);
        }

        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid widget token',
            ], 404);
        }

        $config = $webWidget->getWidgetConfig();

        return response()->json([
            'success' => true,
            'data' => [
                'theme' => [
                    'primary_color' => $config['widgetColor'] ?? '#0066FF',
                    'position' => $config['widgetPosition'] ?? 'bottom-right',
                ],
                'greeting' => [
                    'enabled' => true,
                    'title' => $config['welcomeTitle'] ?? 'Hi! 👋',
                    'message' => $config['welcomeTagline'] ?? 'How can we help you today?',
                ],
                'features' => [
                    'file_upload' => true,
                    'emoji' => true,
                    'helpcenter' => true,
                    'pre_chat_form' => $config['preChatFormEnabled'] ?? false,
                ],
                'business_hours' => [
                    'enabled' => $webWidget->inbox?->working_hours_enabled ?? false,
                ],
                'reply_time' => $config['replyTime'] ?? null,
            ],
        ]);
    }
}
