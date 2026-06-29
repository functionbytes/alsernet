<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Engagement\Http\Requests\Sdk\SetContextRequest;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Services\ScoringService;

class ContextController extends Controller
{
    public function __construct(
        private readonly ScoringService $scoringService,
    ) {}

    public function __invoke(SetContextRequest $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');
        $sessionToken = $request->header('X-Session-Token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere X-Session-Token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $context = VisitorContext::query()->firstOrCreate(
            ['session_token' => $sessionToken],
            [
                'inbox_id' => $inbox->id,
                'customer_id' => null,
                'context' => [],
            ],
        );

        $context->mergeContext($request->input('context'));

        $visitorScore = $this->scoringService->recalculate($sessionToken, $inbox->id);

        return response()->json([
            'success' => true,
            'data' => [
                'context' => $context->context,
                'score' => $visitorScore->score,
                'segment' => $visitorScore->segment,
            ],
        ]);
    }
}
