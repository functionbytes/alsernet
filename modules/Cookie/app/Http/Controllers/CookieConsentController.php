<?php

namespace Modules\Cookie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cookie\Http\Requests\StoreConsentRequest;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Cookie\Models\CookieInventory;

class CookieConsentController extends Controller
{
    public function policy(): View
    {
        $inventory = CookieInventory::query()->active()->ordered()->get()->groupBy('category');
        $categories = config('Cookie.general.cookie_categories', []);

        return view('cookie::policy', compact('inventory', 'categories'));
    }

    public function store(StoreConsentRequest $request): JsonResponse
    {
        $data = $request->safe()->all();

        $ipHash = hash('sha256', $request->ip());

        if (! $request->boolean('is_update')) {
            $alreadyLogged = CookieConsentLog::where('ip_hash', $ipHash)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($alreadyLogged) {
                return response()->json(['success' => true, 'expires' => '365 days']);
            }
        }

        CookieConsentLog::create([
            'session_id' => substr(session()->getId(), 0, 64),
            'user_id' => auth()->id(),
            'ip_hash' => $ipHash,
            'action' => $data['action'],
            'accepted_categories' => $data['accepted_categories'] ?? [],
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'version' => config('Cookie.general.version', '1.0'),
        ]);

        return response()->json(['success' => true, 'expires' => '365 days']);
    }

    public function status(Request $request): JsonResponse
    {
        $cookieName = config('Cookie.general.cookie_name', 'cookie_for_consent');
        $consent = $request->cookie($cookieName);

        if ($consent === null) {
            return response()->json(['has_consent' => false]);
        }

        $decoded = json_decode($consent, true);
        $allowed = ['action', 'categories'];
        $safe = array_intersect_key($decoded ?? [], array_flip($allowed));

        if (isset($safe['categories']) && ! is_array($safe['categories'])) {
            $safe['categories'] = [];
        }

        return response()->json(['has_consent' => true, 'data' => $safe]);
    }
}
