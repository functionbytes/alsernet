<?php

namespace Modules\Helpdesk\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Resources\AgentResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;

/**
 * @group Agents
 *
 * Agent (user) information endpoints.
 */
class AgentsApiController extends Controller
{
    /**
     * Current agent
     *
     * Returns the authenticated agent's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return ApiResponse::success(new AgentResource($user));
    }
}
