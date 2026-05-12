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
     *
     * Exposes all settings consumed by widget-store.ts so the widget always
     * receives values from the single source of truth (the Web model).
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
                // Theme / style
                'theme' => [
                    'primary_color' => $config['primary_color'],
                    'secondary_color' => $config['secondary_color'],
                    'position' => $config['position'],
                    'header_title' => $config['header_title'],
                    'logo_url' => $config['logo_url'],
                    'team_avatars' => $config['team_avatars'],
                ],

                // Greeting / chat screen
                'greeting' => [
                    'enabled' => true,
                    'title' => $config['header_title'],
                    'message' => $config['welcome_message'],
                    'input_placeholder' => $config['input_placeholder'],
                    'queue_message' => $config['queue_message'],
                ],

                // Launcher
                'launcher' => [
                    'position' => $config['position'],
                    'side_spacing' => $config['side_spacing'],
                    'bottom_spacing' => $config['bottom_spacing'],
                    'hide_launcher' => $config['hide_launcher'],
                ],

                // Home screen feature flags
                'home_screen' => [
                    'show_avatars' => $config['show_avatars'],
                    'show_help_center' => $config['show_help_center'],
                    'hide_suggested_articles' => $config['hide_suggested_articles'],
                    'show_tickets_section' => $config['show_tickets_section'],
                    'enable_send_message' => $config['enable_send_message'],
                    'enable_create_ticket' => $config['enable_create_ticket'],
                    'enable_search_help' => $config['enable_search_help'],
                ],

                // Feature flags (chat screen)
                'features' => [
                    'file_upload' => (bool) ($config['enable_file_upload'] ?? true),
                    'emoji' => (bool) ($config['enable_emoji'] ?? true),
                    'allowed_file_types' => $config['allowed_file_types'] ?? ['images', 'documents'],
                    'helpcenter' => $config['show_help_center'],
                    'pre_chat_form' => $config['pre_chat_form_enabled'],
                    'post_chat_form' => $config['post_chat_form_enabled'],
                    'offline_message' => $config['offlineMessageEnabled'],
                    'show_timestamps' => $config['show_timestamps'],
                    'typing_indicator' => $config['typing_indicator'],
                    'sound_notifications' => $config['sound_notifications'],
                    'enable_email_transcripts' => $config['enable_email_transcripts'],
                ],

                // Offline
                'offline' => [
                    'enabled' => $config['offlineMessageEnabled'],
                    'message' => $config['offline_message'],
                ],

                // Forms
                'forms' => [
                    'pre_chat_enabled' => $config['pre_chat_form_enabled'],
                    'pre_chat_info' => $config['pre_chat_info'],
                    'pre_chat_options' => $config['preChatFormOptions'],
                    'post_chat_enabled' => $config['post_chat_form_enabled'],
                    'post_chat_info' => $config['post_chat_info'],
                ],

                // Automation
                'automation' => [
                    'enable_auto_transfer' => $config['enable_auto_transfer'],
                    'auto_transfer_minutes' => $config['auto_transfer_minutes'],
                    'enable_auto_inactive' => $config['enable_auto_inactive'],
                    'auto_inactive_minutes' => $config['auto_inactive_minutes'],
                    'enable_auto_close' => $config['enable_auto_close'],
                    'auto_close_minutes' => $config['auto_close_minutes'],
                ],

                // Business hours
                'business_hours' => [
                    'enabled' => ! empty($config['businessHours']),
                    'schedule' => $config['businessHours'],
                ],

                'reply_time' => $config['replyTime'],
            ],
        ]);
    }
}
