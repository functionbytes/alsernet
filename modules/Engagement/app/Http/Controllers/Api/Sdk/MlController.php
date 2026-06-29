<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Engagement\Services\MlPredictorService;

class MlController extends Controller
{
    public function __construct(
        private readonly MlPredictorService $predictor,
    ) {}

    public function predictConversion(Request $request): JsonResponse
    {
        $sessionToken = $request->input('session_token') ?? $request->header('X-Session-Token');

        if (! $sessionToken) {
            return response()->json(['success' => false, 'message' => 'Se requiere session_token.'], 400);
        }

        $inbox = $request->attributes->get('website_inbox');
        $probability = $this->predictor->predictConversion($sessionToken, $inbox->id);

        return response()->json([
            'success' => true,
            'data' => [
                'conversion_probability' => $probability,
                'tier' => match (true) {
                    $probability >= 70 => 'high',
                    $probability >= 40 => 'medium',
                    default => 'low',
                },
            ],
        ]);
    }

    public function predictNextAction(Request $request): JsonResponse
    {
        $sessionToken = $request->input('session_token') ?? $request->header('X-Session-Token');

        if (! $sessionToken) {
            return response()->json(['success' => false, 'message' => 'Se requiere session_token.'], 400);
        }

        $inbox = $request->attributes->get('website_inbox');
        $prediction = $this->predictor->predictNextAction($sessionToken, $inbox->id);

        return response()->json([
            'success' => true,
            'data' => $prediction,
        ]);
    }
}
