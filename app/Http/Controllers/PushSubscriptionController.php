<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');

        if (! $endpoint) {
            return response()->json(['error' => 'Missing endpoint'], 422);
        }

        DB::table('push_subscriptions')->updateOrInsert(
            ['endpoint' => $endpoint],
            [
                'user_id' => $request->user()->id,
                'p256dh' => $request->input('keys.p256dh'),
                'auth' => $request->input('keys.auth'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        DB::table('push_subscriptions')
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
