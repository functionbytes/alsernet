<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\RecommenderService;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly RecommenderService $recommenderService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $sessionToken = $request->header('X-Session-Token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere X-Session-Token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $inbox = $request->attributes->get('livechat_inbox');
        $limit = (int) $request->input('limit', 5);
        $limit = min(20, max(1, $limit));

        $score = VisitorScore::query()->forSession($sessionToken)->first();
        $customerId = $score?->customer_id;

        $products = $this->recommenderService->recommend($sessionToken, $customerId, $limit, $inbox->id);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
            ],
        ]);
    }
}
