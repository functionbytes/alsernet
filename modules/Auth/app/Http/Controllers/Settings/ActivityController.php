<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\LoginAttempt;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $attempts = LoginAttempt::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('attempted_at')
            ->limit(50)
            ->get()
            ->map(fn (LoginAttempt $a) => [
                'id' => $a->id,
                'status' => $a->status,
                'reason' => $a->reason,
                'ip_address' => $a->ip_address,
                'user_agent' => $a->user_agent,
                'attempted_at' => $a->attempted_at?->toIso8601String(),
            ]);

        return response()->json(['attempts' => $attempts]);
    }
}
