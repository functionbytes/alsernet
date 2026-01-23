<?php

namespace Modules\HelpdeskChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Modules\HelpdeskChat\Models\Channels\Web;

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

        // Load widget JavaScript file
        $widgetPath = resource_path('js/widget/chat-widget.js');

        if (! File::exists($widgetPath)) {
            return response('// Widget script not found', 500)
                ->header('Content-Type', 'application/javascript');
        }

        $javascript = File::get($widgetPath);

        // Inject configuration at the top
        $config = $this->buildWidgetConfig($webWidget);
        $configScript = 'window.chatwootSettings = '.json_encode($config).";\n\n";

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
            'apiUrl' => url('/api/widget'),
        ];
    }
}
