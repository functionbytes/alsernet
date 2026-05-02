<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Engagement\Http\Requests\Sdk\TrackBatchRequest;
use Modules\Engagement\Services\TrackingIngestService;
use Modules\Engagement\Services\TriggerEvaluator;

class TrackController extends Controller
{
    public function __construct(
        private readonly TrackingIngestService $ingestService,
        private readonly TriggerEvaluator $triggerEvaluator,
    ) {}

    public function __invoke(TrackBatchRequest $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');
        $sessionToken = $request->header('X-Session-Token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere X-Session-Token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $accepted = $this->ingestService->ingest(
            events: $request->input('events'),
            sessionToken: $sessionToken,
            inbox: $inbox,
        );

        $triggersFired = $this->triggerEvaluator->evaluate($sessionToken, $inbox->id);

        return response()->json([
            'success' => true,
            'data' => [
                'accepted' => $accepted,
                'rejected' => count($request->input('events')) - $accepted,
                'triggersFired' => $triggersFired,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
