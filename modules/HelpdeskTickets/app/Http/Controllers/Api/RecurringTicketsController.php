<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Models\RecurringTicket;

class RecurringTicketsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $schedules = RecurringTicket::query()
            ->where('is_active', true)
            ->with(['category:id,name', 'priority:id,name'])
            ->orderBy('next_run_at')
            ->paginate($request->input('per_page', 15));

        $data = $schedules->through(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'frequency' => $item->frequency,
            'nextRunAt' => $item->next_run_at?->toIso8601String(),
            'lastRunAt' => $item->last_run_at?->toIso8601String(),
            'ticketsCreated' => $item->tickets_created,
            'category' => $item->category ? ['id' => $item->category->id, 'name' => $item->category->name] : null,
        ]);

        return ApiResponse::success($data);
    }
}
