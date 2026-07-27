<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\Engagement\Http\Resources\Sdk\TriggerRuleResource;
use Modules\Engagement\Models\TriggerRule;

class TriggerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');

        $rules = Cache::remember(
            "engagement:rules:trigger:{$inbox->id}",
            600,
            fn () => TriggerRule::query()->forInbox($inbox->id)->active()->ordered()->get(),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => TriggerRuleResource::collection($rules),
            ],
        ]);
    }
}
