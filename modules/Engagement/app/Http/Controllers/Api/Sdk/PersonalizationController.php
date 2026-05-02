<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Engagement\Http\Resources\Sdk\PersonalizationRuleResource;
use Modules\Engagement\Models\PersonalizationRule;

class PersonalizationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');

        $rules = PersonalizationRule::query()
            ->forInbox($inbox->id)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => PersonalizationRuleResource::collection($rules),
            ],
        ]);
    }
}
