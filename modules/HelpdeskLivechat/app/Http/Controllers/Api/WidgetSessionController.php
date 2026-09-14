<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskLivechat\Http\Requests\Widget\HeartbeatRequest;
use Modules\HelpdeskLivechat\Services\WidgetSessionService;

class WidgetSessionController extends Controller
{
    public function __construct(
        private readonly WidgetSessionService $service
    ) {}

    public function heartbeat(HeartbeatRequest $request): JsonResponse
    {
        $session = $this->service->heartbeat(
            $request->validated('session_token'),
            $request->validated('url'),
            $request->validated('title'),
            $request,
        );

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
        ]);
    }
}
