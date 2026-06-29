<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\PageView;

class PageTrackingController extends Controller
{
    /**
     * Record time-on-page for the most recent view matching this IP hash and page.
     */
    public function trackTime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'time_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
        ]);

        $ipHash = hash('sha256', $request->ip());

        PageView::where('page_id', $validated['page_id'])
            ->where('ip_hash', $ipHash)
            ->whereDate('viewed_date', today())
            ->whereNull('time_on_page')
            ->latest('viewed_at')
            ->limit(1)
            ->update([
                'time_on_page' => $validated['time_seconds'],
                'bounced' => $validated['time_seconds'] < 10,
            ]);

        return response()->json(['ok' => true]);
    }
}
