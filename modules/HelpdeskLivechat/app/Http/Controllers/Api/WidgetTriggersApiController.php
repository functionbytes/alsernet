<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Models\WidgetTrigger;

/**
 * GET /hd/api/triggers?website_token= — disparadores activos del canal para
 * que el widget los evalúe en el navegador. Sin datos del visitante.
 */
class WidgetTriggersApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $token = (string) ($request->header('X-Website-Token') ?: $request->query('website_token', ''));
        $web = $token !== '' ? Web::where('website_token', $token)->first() : null;
        if (! $web) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $triggers = Cache::remember(WidgetTrigger::cacheKey($web->id), 300, fn () => WidgetTrigger::query()
            ->where('web_id', $web->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (WidgetTrigger $t) => $t->toWidgetArray())
            ->all());

        return response()->json(['data' => $triggers])
            ->header('Cache-Control', 'public, max-age=120');
    }
}
