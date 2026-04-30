<?php

namespace Modules\Chat\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Web;

class WidgetScriptController extends Controller
{
    /**
     * Serve widget JavaScript SDK.
     *
     * GET /widget/script/{websiteToken}
     */
    public function script(Request $request, string $websiteToken): Response
    {
        // Validate and find web widget
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response('// Widget not found', 404)
                ->header('Content-Type', 'application/javascript');
        }

        // Load widget embed script from build
        $widgetPath = public_path('build-chat/widget.js');

        if (! File::exists($widgetPath)) {
            // Fallback message with build instructions
            return response(
                '// Widget script not found. Please run: cd modules/Chat && npm run widget:build',
                500
            )->header('Content-Type', 'application/javascript');
        }

        $javascript = File::get($widgetPath);

        // Inject widget configuration for auto-initialization
        $config = $this->buildWidgetConfig($webWidget);
        $configScript = 'window.WIDGET_CONFIG = '.json_encode($config).";\n\n";

        $content = $configScript.$javascript;

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Serve widget configuration.
     *
     * GET /widget/config/{websiteToken}
     */
    public function config(Request $request, string $websiteToken): Response
    {
        // Validate and find web widget
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $config = $webWidget->getWidgetConfig();

        return response()
            ->json($config)
            ->header('Cache-Control', 'public, max-age=300')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Build widget configuration for injection.
     */
    protected function buildWidgetConfig(Web $webWidget): array
    {
        return [
            'websiteToken' => $webWidget->website_token,
            'baseUrl' => url('/'),
            'widgetColor' => $webWidget->widget_color ?? '#1f93ff',
            'position' => $webWidget->widget_position ?? 'right',
            'welcomeTitle' => $webWidget->welcome_title ?? 'Welcome!',
            'welcomeTagline' => $webWidget->welcome_tagline ?? 'How can we help you?',
            'preChatFormEnabled' => $webWidget->pre_chat_form_enabled ?? false,
        ];
    }
}
