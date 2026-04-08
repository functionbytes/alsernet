<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\TicketStatus;

class StatusesController extends Controller
{
    public function index(): JsonResponse
    {
        $statuses = TicketStatus::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'is_open', 'is_default']);

        return response()->json($statuses);
    }
}
