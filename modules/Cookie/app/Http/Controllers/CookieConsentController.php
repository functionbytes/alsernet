<?php

namespace Modules\Cookie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cookie\Models\CookieConsentLog;

class CookieConsentController extends Controller
{
    private const VALID_ACTIONS = ['accept_all', 'reject_all', 'custom'];

    private const VALID_CATEGORIES = ['analytics', 'marketing', 'preferences'];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:'.implode(',', self::VALID_ACTIONS)],
            'accepted_categories' => ['nullable', 'array'],
            'accepted_categories.*' => ['string', 'in:'.implode(',', self::VALID_CATEGORIES)],
            'is_update' => ['nullable', 'boolean'],
        ]);

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

        return response()->json([
            'has_consent' => $consent !== null,
            'consent' => $consent ? json_decode($consent, true) : null,
        ]);
    }
}
