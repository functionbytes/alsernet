<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WidgetController extends Controller
{
    /**
     * Render the SPA widget mountpoint.
     * Routes: /hd/widget and /hd/widget/{any}
     */
    public function index(Request $request): View
    {
        $websiteToken = $request->query('website_token');
        $isPreview = $request->boolean('preview');
        $config = [];
        $webWidget = null;

        if ($websiteToken) {
            $webWidget = Web::where('website_token', $websiteToken)->first();
            if ($webWidget) {
                $config = $webWidget->getWidgetConfig();
            }
        }

        return view('helpdesklivechat::public.widget.spa', [
            'isPreview' => $isPreview,
            'websiteToken' => $websiteToken,
            'webWidget' => $webWidget,
            'config' => $config,
        ]);
    }

    /**
     * GET /hd/api/settings — returns the widget config used by the React store.
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

        $web = Web::where('website_token', $websiteToken)->first();

        if (! $web) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid widget token',
            ], 404);
        }

        $config = $web->getWidgetConfig();

        return response()->json([
            'success' => true,
            'data' => [
                'theme' => [
                    'primary_color' => $config['widgetColor'] ?? '#b10100',
                    'position' => $config['widgetPosition'] ?? 'right',
                ],
                'greeting' => [
                    'enabled' => true,
                    'title' => $config['welcomeTitle'] ?? 'Hi there!',
                    'message' => $config['welcomeTagline'] ?? 'How can we help?',
                ],
                'features' => [
                    'file_upload' => true,
                    'emoji' => true,
                    'helpcenter' => true,
                    'pre_chat_form' => (bool) ($config['preChatFormEnabled'] ?? false),
                    'offline_message' => (bool) ($config['offlineMessageEnabled'] ?? true),
                ],
                'offline' => [
                    'enabled' => (bool) ($config['offlineMessageEnabled'] ?? true),
                    'message' => $config['offlineMessage'] ?? null,
                ],
                'business_hours' => [
                    'enabled' => false,
                ],
                'reply_time' => $config['replyTime'] ?? null,
            ],
        ]);
    }
}
