<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Http\Resources\StatusResource;
use Modules\HelpdeskTickets\Models\TicketStatus;

class StatusesController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $statuses = TicketStatus::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'is_open', 'is_default']);

        return ApiResponse::success(StatusResource::collection($statuses));
    }
}
