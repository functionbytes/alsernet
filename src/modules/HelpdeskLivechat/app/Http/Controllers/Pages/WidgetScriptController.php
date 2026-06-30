<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Modules\HelpdeskLivechat\Concerns\ResolvesReverbHost;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WidgetScriptController extends Controller
{
    use ResolvesReverbHost;

    /**
     * Serve the embed loader JS, prepending window.HELPDESK_WIDGET_CONFIG.
     * GET /widget/helpdesk/script/{websiteToken}
     */
    public function script(Request $request, string $websiteToken): Response
    {
        $web = Web::where('website_token', $websiteToken)->first();

        if (! $web) {
            return response('// Widget not found', 404)
                ->header('Content-Type', 'application/javascript; charset=UTF-8');
        }

        $bundlePath = public_path('build-helpdesklivechat/widget.js');
        if (! File::exists($bundlePath)) {
            return response(
                "// Widget bundle not built yet — run: cd modules/HelpdeskLivechat && npm run widget:build\n",
                503
            )->header('Content-Type', 'application/javascript; charset=UTF-8');
        }

        $config = $this->buildConfig($web);
        $configScript = 'window.HELPDESK_WIDGET_CONFIG = '.json_encode($config).";\n\n";

        $content = $configScript.File::get($bundlePath);

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Serve only the JSON config (useful for non-loader integrations).
     * GET /widget/helpdesk/config/{websiteToken}
     */
    public function config(Request $request, string $websiteToken): JsonResponse
    {
        $web = Web::where('website_token', $websiteToken)->first();

        if (! $web) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        return response()
            ->json($this->buildConfig($web))
            ->header('Cache-Control', 'public, max-age=300')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildConfig(Web $web): array
    {
        return array_merge(
            $web->getWidgetConfig(),
            [
                'reverbKey' => config('broadcasting.connections.reverb.key', ''),
                'reverbHost' => $this->connectableReverbHost(),
                'reverbPort' => (int) config('broadcasting.connections.reverb.options.port', 8080),
                'reverbScheme' => config('broadcasting.connections.reverb.options.scheme', request()->isSecure() ? 'https' : 'http'),
                'channelPrefix' => 'helpdesk-widget-conversation',
                'eventName' => '.message.received',
            ]
        );
    }
}
