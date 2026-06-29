<?php

namespace Modules\Remarketing\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Remarketing\Http\Requests\Public\TrackEventRequest;
use Modules\Remarketing\Jobs\ProcessPixelEventJob;
use Modules\Remarketing\Models\Store;

class TrackController extends Controller
{
    public function track(TrackEventRequest $request): JsonResponse
    {
        $store = Store::query()
            ->where('webhook_token', $request->input('store_token'))
            ->first();

        // Always return 200 to avoid leaking store existence
        if (! $store) {
            return response()->json(['ok' => true]);
        }

        $occurredAt = $request->input('occurred_at')
            ? Carbon::parse($request->input('occurred_at'))
            : now();

        ProcessPixelEventJob::dispatch(
            $store->id,
            null,
            $request->input('visitor_id'),
            $request->input('type'),
            $request->input('properties', []),
            $request->ip(),
            $request->userAgent(),
            $occurredAt,
        )->onQueue('remarketing');

        return response()->json(['ok' => true]);
    }
}
