<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Http\Resources\PriorityResource;
use Modules\HelpdeskTickets\Models\Priority;

class PrioritiesController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $priorities = Priority::where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'slug', 'level', 'color', 'response_time_hours', 'resolution_time_hours']);

        return ApiResponse::success(PriorityResource::collection($priorities));
    }
}
