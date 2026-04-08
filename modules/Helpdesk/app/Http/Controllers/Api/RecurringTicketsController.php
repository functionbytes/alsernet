<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\RecurringTicket;

class RecurringTicketsController extends Controller
{
    /**
     * List active recurring ticket schedules ordered by next run date.
     *
     * GET /api/helpdesk/recurring-tickets
     */
    public function index(): JsonResponse
    {
        $schedules = RecurringTicket::query()
            ->where('is_active', true)
            ->with(['category:id,name', 'priority:id,name'])
            ->orderBy('next_run_at')
            ->get(['id', 'name', 'frequency', 'next_run_at', 'last_run_at', 'tickets_created', 'category_id', 'priority_id']);

        return response()->json($schedules);
    }
}
